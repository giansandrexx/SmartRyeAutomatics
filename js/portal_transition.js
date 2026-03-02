(function () {

  const overlay = document.createElement("div");
  overlay.id = "ntv-overlay";
  overlay.innerHTML = `
    <div class="ntv-box">
      <img src="https://smartrye.com.ph/ams/public/backend/images/logo-sra.png" alt="Logo" class="ntv-logo"/>
      <p class="ntv-text" id="ntv-label">Loading...</p>
      <div class="ntv-bar-track">
        <div class="ntv-bar" id="ntv-bar"></div>
      </div>
    </div>
  `;
  document.body.appendChild(overlay);

  const style = document.createElement("style");
  style.textContent = `
    #ntv-overlay {
      position: fixed;
      inset: 0;
      z-index: 999999;
      background: linear-gradient(145deg, #071a3e 0%, #0d2f6e 45%, #1245a8 75%, #1a6ed8 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    #ntv-overlay::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image:
        linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
      background-size: 40px 40px;
      pointer-events: none;
    }

    #ntv-overlay .ntv-orb-1,
    #ntv-overlay .ntv-orb-2 {
      position: absolute;
      border-radius: 50%;
      pointer-events: none;
      animation: ntv-orb-float 6s ease-in-out infinite;
    }
    #ntv-overlay .ntv-orb-1 {
      width: 380px; height: 380px;
      background: radial-gradient(circle, rgba(33,150,243,0.18), transparent 65%);
      top: -100px; left: -120px;
      animation-delay: 0s;
    }
    #ntv-overlay .ntv-orb-2 {
      width: 300px; height: 300px;
      background: radial-gradient(circle, rgba(66,165,245,0.14), transparent 65%);
      bottom: -80px; right: -80px;
      animation-delay: -3s;
    }

    @keyframes ntv-orb-float {
      0%, 100% { transform: translateY(0) scale(1); }
      50%       { transform: translateY(-20px) scale(1.04); }
    }

    #ntv-overlay.ntv-active {
      opacity: 1;
      pointer-events: all;
    }

    .ntv-box {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 22px;
      position: relative;
      z-index: 1;
      animation: ntv-box-in 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes ntv-box-in {
      from { opacity: 0; transform: translateY(16px) scale(0.96); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .ntv-logo {
      height: 44px;
      width: auto;
      filter: brightness(0) invert(1) drop-shadow(0 2px 12px rgba(33,150,243,0.5));
      animation: ntv-logo-pulse 2s ease-in-out infinite;
    }

    @keyframes ntv-logo-pulse {
      0%, 100% { filter: brightness(0) invert(1) drop-shadow(0 2px 12px rgba(33,150,243,0.4)); }
      50%       { filter: brightness(0) invert(1) drop-shadow(0 2px 22px rgba(33,150,243,0.8)); }
    }

    .ntv-text {
      font-family: 'DM Sans', 'Roboto', sans-serif;
      font-size: 14px;
      font-weight: 500;
      color: rgba(255,255,255,0.75);
      margin: 0;
      letter-spacing: 0.02em;
      transition: opacity 0.2s ease;
    }

    .ntv-bar-track {
      width: 240px;
      height: 3px;
      background: rgba(255,255,255,0.12);
      border-radius: 99px;
      overflow: hidden;
      position: relative;
    }

    .ntv-bar {
      height: 100%;
      width: 0%;
      background: linear-gradient(90deg, #1a6ed8, #42a5f5, #90caf9);
      border-radius: 99px;
      transition: width 0.45s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 0 10px rgba(66,165,245,0.7);
      position: relative;
    }

    .ntv-bar::after {
      content: '';
      position: absolute;
      top: 0; right: 0;
      width: 40px; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5));
      border-radius: 99px;
    }

    .ntv-percent {
      font-family: 'DM Mono', 'Roboto Mono', monospace;
      font-size: 11px;
      font-weight: 500;
      color: rgba(255,255,255,0.4);
      margin: 0;
      letter-spacing: 0.04em;
    }
  `;
  document.head.appendChild(style);

  overlay.insertAdjacentHTML('afterbegin', '<div class="ntv-orb-1"></div><div class="ntv-orb-2"></div>');

  const label   = document.getElementById("ntv-label");
  const bar     = document.getElementById("ntv-bar");

  const percentEl = document.createElement("p");
  percentEl.className = "ntv-percent";
  percentEl.textContent = "0%";
  overlay.querySelector(".ntv-box").appendChild(percentEl);

  function setProgress(pct) {
    bar.style.width = pct + "%";
    percentEl.textContent = pct + "%";
  }

  function navigateTo(destination, roomName) {
    label.textContent = "Opening " + (roomName || destination) + "...";
    setProgress(0);
    overlay.classList.add("ntv-active");

    setTimeout(() => setProgress(30),  120);
    setTimeout(() => setProgress(55),  400);
    setTimeout(() => setProgress(75),  750);
    setTimeout(() => setProgress(90),  1000);
    setTimeout(() => {
      setProgress(100);
      setTimeout(() => { window.location.href = destination; }, 220);
    }, 1200);
  }

  function bindNavLinks() {
    document.querySelectorAll("[data-navigate]").forEach(function (el) {
      el.addEventListener("click", function (e) {
        e.preventDefault();
        navigateTo(
          el.getAttribute("data-navigate"),
          el.getAttribute("data-room-name") || el.getAttribute("data-navigate")
        );
      });
    });
  }

  window.addEventListener("pageshow", function () {
    overlay.classList.remove("ntv-active");
    setProgress(0);
  });

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", bindNavLinks);
  } else {
    bindNavLinks();
  }

  window.navTransition = { navigateTo };
})();