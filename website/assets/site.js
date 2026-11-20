/* FAHRNI Website-Entwurf: Leuchtkegel, Menü, Filter, Galerie, Footer-Video */
(function () {
  var root = document.documentElement;
  var reduce = matchMedia("(prefers-reduced-motion: reduce)").matches;
  var fine = matchMedia("(hover: hover) and (pointer: fine)").matches;
  var tiles = Array.prototype.slice.call(document.querySelectorAll(".tile"));

  /* ---------- Leuchtkegel ---------- */
  var cone = document.querySelector(".cone");
  var halo = cone.querySelector(".cone__halo");
  var dot = cone.querySelector(".cone__dot");
  var mx = innerWidth / 2, my = innerHeight * .3, hx = mx, hy = my, dx = mx, dy = my, ax = mx, ay = my;
  var running = false;

  function frame() {
    var k = reduce ? 1 : .12, kd = reduce ? 1 : .4, ka = reduce ? 1 : .035;
    hx += (mx - hx) * k;  hy += (my - hy) * k;
    dx += (mx - dx) * kd; dy += (my - dy) * kd;
    ax += (mx - ax) * ka; ay += (my - ay) * ka;   // Hintergrund folgt sehr träge = ruhig
    halo.style.translate = hx + "px " + hy + "px";
    dot.style.translate = dx + "px " + dy + "px";
    root.style.setProperty("--mx", ax + "px");
    root.style.setProperty("--my", ay + "px");
    for (var i = 0; i < tiles.length; i++) {
      var t = tiles[i];
      if (!t._vis) continue;
      var r = t.getBoundingClientRect();
      t.style.setProperty("--x", (hx - r.left) + "px");
      t.style.setProperty("--y", (hy - r.top) + "px");
    }
    if (Math.abs(mx - ax) + Math.abs(my - ay) > .5 || Math.abs(mx - hx) + Math.abs(my - hy) > .3) requestAnimationFrame(frame);
    else running = false;
  }
  function kick() { if (!running) { running = true; requestAnimationFrame(frame); } }

  if (fine) {
    root.classList.add("has-cone");
    addEventListener("pointermove", function (e) {
      mx = e.clientX; my = e.clientY;
      cone.classList.remove("is-hidden");
      var el = e.target && e.target.closest ? e.target : null;
      cone.classList.toggle("is-lit", !!(el && el.closest("a.tile, .tile[data-light], .gallery button")));
      cone.classList.toggle("is-link", !!(el && el.closest("a, button, input")) && !(el && el.closest(".tile, .gallery")));
      kick();
    }, { passive: true });
    addEventListener("pointerdown", function () { cone.classList.add("is-down"); });
    addEventListener("pointerup", function () { cone.classList.remove("is-down"); });
    document.addEventListener("pointerleave", function () { cone.classList.add("is-hidden"); });
    addEventListener("scroll", kick, { passive: true });
  } else {
    addEventListener("scroll", touchLight, { passive: true });
    setTimeout(touchLight, 300);
  }
  function touchLight() {
    var c = innerHeight / 2, best = null, bd = 1e9;
    tiles.forEach(function (t) {
      if (!(t.matches("a.tile, .tile[data-light]"))) return;
      var r = t.getBoundingClientRect();
      var d = Math.abs(r.top + r.height / 2 - c);
      if (d < bd && r.bottom > 0 && r.top < innerHeight) { bd = d; best = t; }
    });
    tiles.forEach(function (t) {
      var on = t === best;
      t.classList.toggle("is-touched", on);
      if (on) { t.style.setProperty("--x", "50%"); t.style.setProperty("--y", "40%"); }
    });
    root.style.setProperty("--mx", "50%"); root.style.setProperty("--my", "30%");
  }

  /* ---------- Sichtbarkeit + Einblenden ---------- */
  if ("IntersectionObserver" in window) {
    var visIO = new IntersectionObserver(function (es) {
      es.forEach(function (e) { e.target._vis = e.isIntersecting; });
    }, { rootMargin: "200px" });
    tiles.forEach(function (t) { visIO.observe(t); });
    var revIO = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add("is-in"); revIO.unobserve(e.target); } });
    }, { threshold: .1 });
    document.querySelectorAll(".reveal").forEach(function (el) { revIO.observe(el); });
  } else {
    tiles.forEach(function (t) { t._vis = true; });
    document.querySelectorAll(".reveal").forEach(function (el) { el.classList.add("is-in"); });
  }
  kick();

  /* ---------- Kopfleiste ---------- */
  var header = document.getElementById("header"), lastY = 0;
  addEventListener("scroll", function () {
    var y = scrollY;
    header.classList.toggle("is-hidden", y > lastY && y > 300 && !document.body.classList.contains("menu-open"));
    lastY = y;
  }, { passive: true });

  /* ---------- Burger-Menü ---------- */
  var burger = document.getElementById("burger");
  function setMenu(open) {
    document.body.classList.toggle("menu-open", open);
    burger.setAttribute("aria-expanded", String(open));
    burger.setAttribute("aria-label", open ? "Menü schliessen" : "Menü öffnen");
    header.classList.remove("is-hidden");
  }
  burger.addEventListener("click", function () { setMenu(!document.body.classList.contains("menu-open")); });
  document.querySelectorAll(".menu a").forEach(function (a) { a.addEventListener("click", function () { setMenu(false); }); });
  addEventListener("keydown", function (e) { if (e.key === "Escape") { setMenu(false); closeLb(); } });

  /* ---------- Referenzen filtern ---------- */
  var refGrid = document.getElementById("ref-grid");
  if (refGrid) {
    var chips = document.querySelectorAll(".chip[data-group]");
    var search = document.getElementById("ref-search");
    var empty = document.getElementById("ref-empty");
    var group = "alle";
    try { group = sessionStorage.getItem("ref-group") || "alle"; } catch (e) {}
    function apply() {
      var q = (search.value || "").trim().toLowerCase(), shown = 0;
      refGrid.querySelectorAll(".t-ref").forEach(function (t) {
        var ok = (group === "alle" || t.dataset.group === group) && (!q || t.dataset.search.indexOf(q) > -1);
        t.hidden = !ok; if (ok) shown++;
      });
      chips.forEach(function (c) { c.setAttribute("aria-pressed", String(c.dataset.group === group)); });
      empty.hidden = shown > 0;
      tiles.forEach(function (t) { t._vis = !t.hidden; });
    }
    chips.forEach(function (c) { c.addEventListener("click", function () {
      group = c.dataset.group; try { sessionStorage.setItem("ref-group", group); } catch (e) {} apply();
    }); });
    search.addEventListener("input", apply);
    apply();
  }

  /* ---------- Galerie / Lightbox ---------- */
  var lb = document.getElementById("lightbox"), lbImg, lbCount, shots = [], idx = 0;
  if (lb) {
    lbImg = lb.querySelector("img"); lbCount = lb.querySelector(".lb-count");
    shots = Array.prototype.slice.call(document.querySelectorAll(".gallery button"));
    shots.forEach(function (b, i) { b.addEventListener("click", function () { openLb(i); }); });
    lb.querySelector(".lb-close").addEventListener("click", closeLb);
    lb.querySelector(".lb-prev").addEventListener("click", function () { openLb(idx - 1); });
    lb.querySelector(".lb-next").addEventListener("click", function () { openLb(idx + 1); });
    lb.addEventListener("click", function (e) { if (e.target === lb) closeLb(); });
    addEventListener("keydown", function (e) {
      if (!lb.classList.contains("is-open")) return;
      if (e.key === "ArrowRight") openLb(idx + 1);
      if (e.key === "ArrowLeft") openLb(idx - 1);
    });
  }
  function openLb(i) {
    idx = (i + shots.length) % shots.length;
    var im = shots[idx].querySelector("img");
    lbImg.src = im.src; lbImg.alt = im.alt;
    lbCount.textContent = (idx + 1) + " / " + shots.length;
    lb.classList.add("is-open"); lb.querySelector(".lb-close").focus();
  }
  function closeLb() {
    if (!lb || !lb.classList.contains("is-open")) return;
    lb.classList.remove("is-open"); if (shots[idx]) shots[idx].focus();
  }


  /* ---------- Zeitachse: füllt sich beim Scrollen ---------- */
  var tl = document.getElementById("tl");
  if (tl) {
    var items = Array.prototype.slice.call(tl.querySelectorAll(".tl-item"));
    var yearEl = document.getElementById("tl-year"), shownYear = yearEl.textContent;
    var tlTick = false;
    var tlUpdate = function () {
      tlTick = false;
      var r = tl.getBoundingClientRect(), mark = innerHeight * .62;
      var prog = reduce ? 1 : Math.max(0, Math.min(1, (mark - r.top) / r.height));
      tl.style.setProperty("--p", (prog * 100).toFixed(2) + "%");
      var year = items[0].dataset.year;
      items.forEach(function (it) {
        var on = reduce || it.getBoundingClientRect().top + 38 < mark;
        if (on && !it.classList.contains("is-active")) {
          it.classList.add("is-active", "just-on");
          setTimeout(function () { it.classList.remove("just-on"); }, 1200);
        } else if (!on && it.classList.contains("is-active")) {
          it.classList.remove("is-active");
        }
        if (on) year = it.dataset.year;
      });
      if (year !== shownYear) {
        shownYear = year; yearEl.textContent = year;
        yearEl.classList.remove("flip"); void yearEl.offsetWidth; yearEl.classList.add("flip");
      }
    };
    addEventListener("scroll", function () { if (!tlTick) { tlTick = true; requestAnimationFrame(tlUpdate); } }, { passive: true });
    addEventListener("resize", tlUpdate);
    tlUpdate();
  }

  /* ---------- Footer-Animation ---------- */
  var video = document.getElementById("footer-video");
  var toggle = document.getElementById("video-toggle");
  if (video) {
    var userPaused = reduce;
    if (matchMedia("(max-width: 640px)").matches) video.poster = video.dataset.posterMobile;
    var icon = function (name, label) { toggle.innerHTML = '<svg><use href="#i-' + name + '"/></svg>'; toggle.setAttribute("aria-label", label); };
    if (reduce) icon("play", "Animation abspielen");
    if ("IntersectionObserver" in window) {
      new IntersectionObserver(function (es) {
        es.forEach(function (e) {
          if (e.isIntersecting && !userPaused) video.play().catch(function () {});
          else video.pause();
        });
      }, { threshold: .35 }).observe(video);
    }
    toggle.addEventListener("click", function () {
      if (video.paused) { userPaused = false; video.play().catch(function () {}); icon("pause", "Animation pausieren"); }
      else { userPaused = true; video.pause(); icon("play", "Animation abspielen"); }
    });
  }
})();
