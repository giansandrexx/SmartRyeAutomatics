(function () {

    const overlay = document.createElement('div');
    overlay.id = 'deleteConfirmModal';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(10,15,30,0.72);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);z-index:99999;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity 0.25s ease;';

    overlay.innerHTML = `
        <div id="deleteConfirmBox" style="background:#fff;border-radius:20px;width:380px;max-width:92vw;box-shadow:0 32px 80px rgba(0,0,0,0.28),0 0 0 1px rgba(0,0,0,0.06);overflow:hidden;transform:scale(0.88) translateY(20px);transition:transform 0.28s cubic-bezier(0.34,1.56,0.64,1),opacity 0.25s ease;opacity:0;">
            <div style="background:linear-gradient(135deg,#b71c1c,#e53935);padding:28px 28px 22px;display:flex;flex-direction:column;align-items:center;gap:12px;">
                <div style="width:60px;height:60px;background:rgba(255,255,255,0.18);border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid rgba(255,255,255,0.35);">
                    <i class="fas fa-trash-alt" style="font-size:24px;color:#fff;"></i>
                </div>
                <h3 style="margin:0;font-size:18px;font-weight:700;color:#fff;letter-spacing:0.3px;">Delete Item</h3>
            </div>
            <div style="padding:22px 28px 10px;text-align:center;">
                <p style="margin:0 0 6px;font-size:14px;color:#546e7a;line-height:1.6;">You are about to permanently delete:</p>
                <div id="deleteConfirmName" style="display:inline-block;margin:8px 0 0;background:#ffeaea;color:#c62828;border:1px solid #ffcdd2;border-radius:8px;padding:6px 14px;font-size:14px;font-weight:600;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></div>
                <div style="display:flex;align-items:center;gap:8px;background:#fff8e1;border:1px solid #ffe082;border-radius:10px;padding:10px 14px;margin:14px 0 0;text-align:left;">
                    <i class="fas fa-exclamation-triangle" style="color:#f9a825;font-size:14px;flex-shrink:0;"></i>
                    <span style="font-size:12.5px;color:#6d4c00;line-height:1.5;">This action cannot be undone. The item will be permanently removed.</span>
                </div>
            </div>
            <div style="padding:18px 28px 24px;display:flex;gap:10px;">
                <button id="deleteConfirmCancelBtn" style="flex:1;padding:11px;border-radius:10px;border:2px solid #e0e7ef;background:#fff;color:#546e7a;font-size:14px;font-weight:600;cursor:pointer;transition:all 0.18s;">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button id="deleteConfirmOkBtn" style="flex:1;padding:11px;border-radius:10px;border:none;background:linear-gradient(135deg,#c62828,#e53935);color:#fff;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;transition:all 0.18s;box-shadow:0 4px 14px rgba(229,57,53,0.35);">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            </div>
        </div>`;

    document.body.appendChild(overlay);

    const box       = document.getElementById('deleteConfirmBox');
    const cancelBtn = document.getElementById('deleteConfirmCancelBtn');
    const okBtn     = document.getElementById('deleteConfirmOkBtn');
    const nameEl    = document.getElementById('deleteConfirmName');

    let pendingCallback = null;

    function open(name, callback) {
        pendingCallback = callback;
        nameEl.textContent = name;
        overlay.style.opacity = '1';
        overlay.style.pointerEvents = 'all';
        box.style.transform = 'scale(1) translateY(0)';
        box.style.opacity = '1';
    }

    function close() {
        overlay.style.opacity = '0';
        overlay.style.pointerEvents = 'none';
        box.style.transform = 'scale(0.88) translateY(20px)';
        box.style.opacity = '0';
        pendingCallback = null;
        okBtn.disabled = false;
        okBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Delete';
    }

    cancelBtn.addEventListener('click', close);
    overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });

    okBtn.addEventListener('click', function () {
        if (!pendingCallback) return;
        okBtn.disabled = true;
        okBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting…';
        pendingCallback();
    });

    cancelBtn.addEventListener('mouseenter', function () {
        this.style.borderColor = '#b0bec5';
        this.style.background = '#f5f7fa';
        this.style.color = '#37474f';
    });
    cancelBtn.addEventListener('mouseleave', function () {
        this.style.borderColor = '#e0e7ef';
        this.style.background = '#fff';
        this.style.color = '#546e7a';
    });
    okBtn.addEventListener('mouseenter', function () {
        if (this.disabled) return;
        this.style.background = 'linear-gradient(135deg,#b71c1c,#c62828)';
        this.style.boxShadow = '0 6px 18px rgba(229,57,53,0.45)';
        this.style.transform = 'translateY(-1px)';
    });
    okBtn.addEventListener('mouseleave', function () {
        this.style.background = 'linear-gradient(135deg,#c62828,#e53935)';
        this.style.boxShadow = '0 4px 14px rgba(229,57,53,0.35)';
        this.style.transform = 'translateY(0)';
    });

    window.confirmDelete = function (name, callback) {
        open(name, callback);
    };

})();