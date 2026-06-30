                </div><!-- /.page-content -->
            </div><!-- /.page-wrapper -->
        </main>
    </div><!-- /.app-shell -->

    <?php if (!empty($_SESSION['preview_ui'])): ?>
    <div class="preview-ui-banner">
        <span class="pv-dot"></span>
        <span class="pv-text">Previewing Modern UI</span>
        <a href="?confirm_ui=1" class="preview-ui-confirm">✓ Confirm &amp; Apply</a>
        <a href="?cancel_ui=1"  class="preview-ui-cancel">✕ Cancel</a>
    </div>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initThemeToggle();
            initModals();
            initGlobalSearch();
        });
    </script>

    <!-- Global confirm / prompt modals -->
    <div id="confirmModal" class="modal-overlay" data-modal style="z-index:2600;">
        <div class="modal-box" style="max-width:420px;" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
            <div id="confirmHeader" style="background:linear-gradient(135deg,#475569 0%,#334155 100%);margin:-24px -24px 24px;padding:18px 24px;border-radius:var(--radius-sm) var(--radius-sm) 0 0;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div id="confirmIconWrap" style="width:36px;height:36px;background:rgba(255,255,255,0.18);border-radius:10px;display:grid;place-items:center;flex-shrink:0;">
                        <i id="confirmIcon" data-lucide="alert-circle" style="width:18px;height:18px;color:#fff;"></i>
                    </div>
                    <div id="confirmTitle" style="color:#fff;font-size:1rem;font-weight:700;">Confirm</div>
                </div>
                <button class="modal-close" data-close="confirmModal" style="color:#fff;opacity:.75;font-size:1.5rem;">×</button>
            </div>
            <div class="modal-body" id="confirmBody" style="font-size:.92rem;line-height:1.6;"></div>
            <div class="modal-footer">
                <button id="confirmCancel" class="btn btn-outline">Cancel</button>
                <button id="confirmOk" class="btn btn-primary">OK</button>
            </div>
        </div>
    </div>

    <div id="promptModal" class="modal-overlay" data-modal style="z-index:2600;">
        <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="promptTitle">
            <div style="background:linear-gradient(135deg,#475569 0%,#334155 100%);margin:-24px -24px 24px;padding:18px 24px;border-radius:var(--radius-sm) var(--radius-sm) 0 0;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:36px;height:36px;background:rgba(255,255,255,0.18);border-radius:10px;display:grid;place-items:center;flex-shrink:0;">
                        <i data-lucide="message-square" style="width:18px;height:18px;color:#fff;"></i>
                    </div>
                    <div id="promptTitle" style="color:#fff;font-size:1rem;font-weight:700;">Input</div>
                </div>
                <button class="modal-close" data-close="promptModal" style="color:#fff;opacity:.75;font-size:1.5rem;">×</button>
            </div>
            <div class="modal-body">
                <div id="promptBody"></div>
                <input id="promptInput" class="form-input" style="margin-top:12px;" />
            </div>
            <div class="modal-footer">
                <button id="promptCancel" class="btn btn-outline">Cancel</button>
                <button id="promptOk" class="btn btn-primary">OK</button>
            </div>
        </div>
    </div>

    <script>
        try {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            } else {
                setTimeout(function() { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 500);
            }
        } catch (e) {
            console.error('lucide init error', e);
        }
    </script>
</body>
</html>
