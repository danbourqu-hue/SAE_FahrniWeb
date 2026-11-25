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
    var lbl = burger.querySelector(".mburger-label"); if (lbl) lbl.textContent = open ? "Schliessen" : "Menü";
    if (open) { var first = document.querySelector("#menu a"); if (first) setTimeout(function () { first.focus({ preventScroll: true }); }, 350); }
    header.classList.remove("is-hidden");
  }
  burger.addEventListener("click", function () { setMenu(!document.body.classList.contains("menu-open")); });
  document.querySelectorAll(".menu a").forEach(function (a) { a.addEventListener("click", function () { setMenu(false); }); });
  addEventListener("keydown", function (e) { if (e.key === "Escape") { setMenu(false); closeLb(); } });

  /* ---------- Burger-Menü: Bild je Menüpunkt ---------- */
  document.querySelectorAll(".mm-link").forEach(function (a) {
    var show = function () { document.querySelectorAll(".mm-visual img").forEach(function (im) { im.classList.toggle("is-on", im.dataset.pic === a.dataset.pic); }); };
    a.addEventListener("mouseenter", show); a.addEventListener("focus", show);
  });
  if (document.querySelector(".mmenu")) document.body.addEventListener("keydown", function (e) {
    if (!document.body.classList.contains("menu-open") || e.key !== "Tab") return;  // Fokus im offenen Menü halten
    var f = [burger].concat(Array.prototype.slice.call(document.querySelectorAll("#menu a")));
    var i = f.indexOf(document.activeElement);
    if (e.shiftKey && i <= 0) { e.preventDefault(); f[f.length - 1].focus(); }
    else if (!e.shiftKey && i === f.length - 1) { e.preventDefault(); f[0].focus(); }
  });

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
    var qp = new URLSearchParams(location.search).get("q");
    if (qp) { search.value = qp; group = "alle"; }
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


  /* ---------- Zeitachse: vertikales Scrollen bewegt die Achse horizontal ---------- */
  var tl = document.getElementById("tl");
  if (tl) {
    var track = tl.querySelector(".tl-track"), items = Array.prototype.slice.call(tl.querySelectorAll(".tl-item"));
    var yearEl = document.getElementById("tl-year"), shownYear = yearEl.textContent, dist = 0, tlTick = false;
    var setYear = function (year) {
      if (year === shownYear) return;
      shownYear = year; yearEl.textContent = year;
      yearEl.classList.remove("flip"); void yearEl.offsetWidth; yearEl.classList.add("flip");
    };
    var layout = function () {
      if (reduce) { tl.classList.add("is-static"); var vpEl = tl.querySelector(".tl-viewport"); vpEl.tabIndex = 0; vpEl.setAttribute("role", "region"); vpEl.setAttribute("aria-label", "Zeitachse, seitlich scrollbar"); items.forEach(function (it) { it.classList.add("is-active"); });
        tl.style.setProperty("--fill", track.scrollWidth + "px"); tl.style.setProperty("--p", "100%"); setYear(items[items.length - 1].dataset.year); return; }
      dist = Math.max(0, track.scrollWidth - innerWidth);
      tl.style.height = (innerHeight + dist) + "px";   // so lange bleibt die Achse stehen
      tlUpdate();
    };
    var tlUpdate = function () {
      tlTick = false; if (reduce) return;
      var r = tl.getBoundingClientRect();
      var prog = dist ? Math.max(0, Math.min(1, -r.top / dist)) : 0;
      var x = prog * dist;
      track.style.transform = "translate3d(" + (-x) + "px,0,0)";
      var mark = x + innerWidth * .55;             // Lesemarke: 55 % der Bildschirmbreite
      var lastX = items[items.length - 1].offsetLeft;
      var fill = Math.min(mark, lastX + 1);
      if (prog >= .999) fill = lastX + 1;
      tl.style.setProperty("--fill", Math.max(0, fill) + "px");
      tl.style.setProperty("--p", (prog * 100).toFixed(2) + "%");
      var year = items[0].dataset.year;
      items.forEach(function (it) {
        var on = it.offsetLeft <= fill;
        if (on && !it.classList.contains("is-active")) {
          it.classList.add("is-active", "just-on");
          setTimeout(function () { it.classList.remove("just-on"); }, 1200);
        } else if (!on && it.classList.contains("is-active")) it.classList.remove("is-active");
        if (on) year = it.dataset.year;
      });
      setYear(year);
    };
    addEventListener("scroll", function () { if (!tlTick) { tlTick = true; requestAnimationFrame(tlUpdate); } }, { passive: true });
    addEventListener("resize", layout);
    /* Pfeile, Tastatur und Hinweis */
    var prevB = document.getElementById("tl-prev"), nextB = document.getElementById("tl-next"), hint = document.getElementById("tl-hint");
    var activeIdx = function () { var n = -1; items.forEach(function (it, i) { if (it.classList.contains("is-active")) n = i; }); return n; };
    var hideHint = function () { if (hint) hint.classList.add("is-gone"); };
    var goItem = function (i) {
      i = Math.max(0, Math.min(items.length - 1, i));
      if (reduce) { tl.querySelector(".tl-viewport").scrollTo({ left: items[i].offsetLeft - 40, behavior: "auto" }); return; }
      var tlTop = tl.getBoundingClientRect().top + scrollY;
      var x = Math.max(0, Math.min(dist, items[i].offsetLeft - innerWidth * .55 + 2));
      if (i === items.length - 1) x = dist;
      scrollTo({ top: tlTop + x, behavior: "smooth" });
      hideHint();
    };
    var syncArrows = function () { var n = activeIdx(); prevB.disabled = n <= 0; nextB.disabled = n >= items.length - 1; };
    prevB.addEventListener("click", function () { goItem(activeIdx() - 1); });
    nextB.addEventListener("click", function () { goItem(activeIdx() + 1); });
    addEventListener("keydown", function (e) {
      if (document.querySelector("dialog[open]") || document.body.classList.contains("menu-open")) return;
      var r = tl.getBoundingClientRect(); if (r.top > 1 || r.bottom < innerHeight - 1) return;   // nur wenn die Achse fixiert ist
      if (e.key === "ArrowRight") { e.preventDefault(); goItem(activeIdx() + 1); }
      if (e.key === "ArrowLeft") { e.preventDefault(); goItem(activeIdx() - 1); }
    });
    var startY = null;
    addEventListener("scroll", function () {
      var r = tl.getBoundingClientRect();
      if (r.top <= 0 && startY === null) startY = scrollY;
      if (startY !== null && Math.abs(scrollY - startY) > 120) hideHint();
      syncArrows();
    }, { passive: true });
    setTimeout(syncArrows, 50);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(layout);
    layout();
  }

  /* ---------- Social-Media-Slider ---------- */
  var sl = document.getElementById("slider");
  if (sl) {
    root.classList.add("js-sl");
    var slTrack = document.getElementById("sl-track");
    var slides = Array.prototype.slice.call(sl.querySelectorAll(".sl-slide"));
    var dots = Array.prototype.slice.call(document.querySelectorAll(".sl-dot, .sl-thumb"));
    var count = document.getElementById("sl-count"), playBtn = document.getElementById("sl-play");
    var cur = -1, auto = !reduce, inView = false, hold = false;
    var pad2 = function (n) { return (n < 10 ? "0" : "") + n; };
    slides.forEach(function (sd) {
      var ws = sd.querySelectorAll(".sl-text .w").length;
      sd.style.setProperty("--words", Math.min(ws, 45));
      var v = sd.querySelector("video"), bar = sd.querySelector(".sl-bar");
      v.addEventListener("timeupdate", function () { if (v.duration) bar.style.setProperty("--t", (v.currentTime / v.duration * 100) + "%"); });
      v.addEventListener("ended", function () {
        if (auto && !hold && +sd.dataset.i === cur) go(cur + 1); else { v.currentTime = 0; v.play().catch(function () {}); }
      });
    });
    var activate = function (i) {
      if (i === cur) return;
      cur = i;
      slides.forEach(function (sd, k) {
        var on = k === i, v = sd.querySelector("video");
        sd.classList.toggle("is-active", on);
        sd.setAttribute("aria-hidden", on ? "false" : "true");
        sd.querySelectorAll("a, button").forEach(function (el) { if (on) el.removeAttribute("tabindex"); else el.setAttribute("tabindex", "-1"); });
        if (on && inView) { v.currentTime = 0; v.play().catch(function () {}); } else v.pause();
      });
      dots.forEach(function (d) { d.setAttribute("aria-current", String(+d.dataset.go === i)); });
      count.textContent = pad2(i + 1) + " / " + pad2(slides.length);
      if (history.replaceState) history.replaceState(null, "", "#" + slides[i].id);
    };
    var go = function (i) {
      i = (i + slides.length) % slides.length;
      slTrack.scrollTo({ left: slides[i].offsetLeft - slTrack.offsetLeft, behavior: reduce ? "auto" : "smooth" });
      activate(i);
    };
    // aktiven Beitrag aus der Scrollposition ableiten (Wischen auf dem Handy)
    var st;
    slTrack.addEventListener("scroll", function () {
      clearTimeout(st);
      st = setTimeout(function () { activate(Math.round(slTrack.scrollLeft / slTrack.clientWidth)); }, 90);
    }, { passive: true });
    document.getElementById("sl-prev").addEventListener("click", function () { go(cur - 1); });
    document.getElementById("sl-next").addEventListener("click", function () { go(cur + 1); });
    dots.forEach(function (d) { d.addEventListener("click", function () {
      go(+d.dataset.go);
      if (d.classList.contains("sl-thumb")) sl.scrollIntoView({ behavior: reduce ? "auto" : "smooth", block: "start" });
    }); });
    var setAuto = function (on) {
      auto = on;
      playBtn.innerHTML = '<svg aria-hidden="true"><use href="#i-' + (on ? "pause" : "play") + '"/></svg>';
      playBtn.setAttribute("aria-label", on ? "Automatisches Weiterschalten pausieren" : "Automatisch weiterschalten");
    };
    playBtn.addEventListener("click", function () { setAuto(!auto); });
    setAuto(auto);
    sl.addEventListener("mouseenter", function () { hold = true; });
    sl.addEventListener("mouseleave", function () { hold = false; });
    sl.addEventListener("focusin", function (e) { hold = !!(e.target.matches && e.target.matches(":focus-visible")); });  // nur Tastatur-Fokus hält an
    sl.addEventListener("focusout", function () { hold = false; });
    sl.addEventListener("keydown", function (e) {
      if (e.key === "ArrowRight") { e.preventDefault(); go(cur + 1); }
      if (e.key === "ArrowLeft") { e.preventDefault(); go(cur - 1); }
    });
    if ("IntersectionObserver" in window) {
      new IntersectionObserver(function (es) {
        es.forEach(function (e) {
          inView = e.isIntersecting;
          var v = cur > -1 && slides[cur].querySelector("video");
          if (v) { if (inView) v.play().catch(function () {}); else v.pause(); }
        });
      }, { threshold: .25 }).observe(slTrack);
    } else inView = true;
    var start = 0;
    slides.forEach(function (sd, k) { if (location.hash === "#" + sd.id) start = k; });
    requestAnimationFrame(function () { slTrack.scrollTo({ left: slides[start].offsetLeft - slTrack.offsetLeft, behavior: "auto" }); activate(start); });
  }

  /* ---------- Social-Kacheln auf der Startseite: Video beim Darüberfahren ---------- */
  document.querySelectorAll(".sm-tile").forEach(function (t) {
    var v = t.querySelector("video");
    t.addEventListener("mouseenter", function () { v.play().catch(function () {}); });
    t.addEventListener("mouseleave", function () { v.pause(); });
  });

  /* ---------- Meilenstein-Details ---------- */
  var msOpen = function (id) {
    var d = document.getElementById(id); if (!d || !d.showModal) return;
    document.querySelectorAll("dialog.ms[open]").forEach(function (o) { o.close(); });
    root.classList.remove("has-cone");            // eigener Cursor liegt unter dem Dialog
    d.showModal(); d.querySelector(".ms-body").scrollTop = 0;
  };
  document.querySelectorAll("[data-ms-go]").forEach(function (b) {
    b.addEventListener("click", function (e) { e.stopPropagation(); msOpen(b.dataset.msGo); });
  });
  document.querySelectorAll(".tl-card").forEach(function (c) {
    c.addEventListener("click", function (e) { var b = c.querySelector("[data-ms-go]"); if (b && !e.target.closest("a")) msOpen(b.dataset.msGo); });
  });
  document.querySelectorAll("dialog.ms").forEach(function (d) {
    d.querySelector(".ms-close").addEventListener("click", function () { d.close(); });
    d.addEventListener("click", function (e) { if (e.target === d) d.close(); });   // Klick auf Hintergrund
    d.addEventListener("close", function () { if (fine && !document.querySelector("dialog.ms[open]")) root.classList.add("has-cone"); });
  });

  /* ---------- «Zurück»: zur vorherigen Seite der Website, sonst zur Übersicht ---------- */
  document.querySelectorAll("[data-back]").forEach(function (a) {
    a.addEventListener("click", function (e) {
      var ref = document.referrer;
      if (ref && history.length > 1 && (ref.indexOf(location.origin) === 0 || (location.protocol === "file:" && ref.indexOf("file:") === 0))) {
        e.preventDefault(); history.back();
      }
    });
  });

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
