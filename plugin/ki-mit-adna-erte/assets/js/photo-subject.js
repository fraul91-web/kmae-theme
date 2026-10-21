/*
 * Autó-felismerés a fotón — a böngészőben, külső szolgáltatás nélkül.
 *
 * COCO-SSD (TensorFlow.js, Apache-2.0) megkeresi a képen a járművet, és a
 * dobozát 0–1 arányban adja vissza: [bal, fent, jobb, lent]. A böngésző v2
 * kártyája ebből tudja, mennyit nagyíthat és olvaszthat a fotón úgy, hogy
 * az autót ne vágja le (KMAE_Images::card_sources 'subject').
 *
 * A modell csak az első kérésnél töltődik be; a képeket egyesével, sorban
 * nézi (telefonon se fogyjon el a memória). Hiba esetén null — a kártya
 * ilyenkor a biztonságos alapértékekkel dolgozik.
 */
(function () {
  var TF_URL = 'https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.22.0/dist/tf.min.js';
  var COCO_URL = 'https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd@2.2.3/dist/coco-ssd.min.js';
  var VEHICLES = { car: 1, truck: 1, bus: 1, motorcycle: 1 };
  var MIN_SCORE = 0.3;
  var EDGE = 640;

  var modelPromise = null;
  var chain = Promise.resolve();

  function loadScript(src) {
    return new Promise(function (resolve, reject) {
      var script = document.createElement('script');
      script.src = src;
      script.async = true;
      script.crossOrigin = 'anonymous';
      script.onload = resolve;
      script.onerror = reject;
      document.head.appendChild(script);
    });
  }

  function model() {
    if (!modelPromise) {
      modelPromise = (window.tf ? Promise.resolve() : loadScript(TF_URL)).then(function () {
        return window.cocoSsd ? null : loadScript(COCO_URL);
      }).then(function () {
        return window.cocoSsd.load({ base: 'mobilenet_v2' });
      });
      modelPromise.catch(function () {
        modelPromise = null;
      });
    }
    return modelPromise;
  }

  /* File/Blob vagy URL → betöltött <img> (az EXIF-forgatást a böngésző alkalmazza). */
  function toImage(source) {
    return new Promise(function (resolve, reject) {
      var isBlob = typeof source !== 'string';
      var url = isBlob ? URL.createObjectURL(source) : source;
      var img = new Image();
      if (!isBlob) {
        img.crossOrigin = 'anonymous';
      }
      img.onload = function () {
        if (isBlob) {
          URL.revokeObjectURL(url);
        }
        resolve(img);
      };
      img.onerror = function () {
        if (isBlob) {
          URL.revokeObjectURL(url);
        }
        reject(new Error('image'));
      };
      img.src = url;
    });
  }

  function round(v) {
    return Math.round(Math.min(1, Math.max(0, v)) * 1000) / 1000;
  }

  /* A fő jármű: a legnagyobb (terület × biztonság) — a háttérben parkoló autók kisebbek. */
  function pick(predictions, w, h) {
    var best = null;
    var bestScore = 0;
    predictions.forEach(function (p) {
      if (!VEHICLES[p['class']] || p.score < MIN_SCORE) {
        return;
      }
      var weight = (p.bbox[2] * p.bbox[3]) / (w * h) * p.score;
      if (weight > bestScore) {
        bestScore = weight;
        best = p;
      }
    });
    if (!best || best.bbox[2] * best.bbox[3] < w * h * 0.04) {
      return null;
    }
    var b = best.bbox;
    return [round(b[0] / w), round(b[1] / h), round((b[0] + b[2]) / w), round((b[1] + b[3]) / h)];
  }

  function run(source) {
    return model().then(function (net) {
      return toImage(source).then(function (img) {
        var w = img.naturalWidth;
        var h = img.naturalHeight;
        var scale = Math.min(1, EDGE / Math.max(w, h));
        var canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(w * scale));
        canvas.height = Math.max(1, Math.round(h * scale));
        canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
        return net.detect(canvas, 20, MIN_SCORE).then(function (predictions) {
          return pick(predictions, canvas.width, canvas.height);
        });
      });
    });
  }

  /** @return {Promise<number[]|null>} [x0, y0, x1, y1] 0–1 között, vagy null. Sosem dob hibát. */
  function detect(source) {
    var next = chain.then(function () {
      return run(source);
    }).catch(function () {
      return null;
    });
    chain = next;
    return next;
  }

  window.kmaePhotoSubject = { detect: detect };
})();
