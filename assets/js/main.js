// Jay影视 主脚本
(function () {
    'use strict';

    // 主题颜色设置
    function applyTheme() {
        try {
            const color = document.body.getAttribute('data-theme') || getComputedStyle(document.documentElement).getPropertyValue('--theme-color').trim();
            if (!color) return;
            const hex = color.replace('#', '');
            const r = parseInt(hex.substring(0, 2), 16);
            const g = parseInt(hex.substring(2, 4), 16);
            const b = parseInt(hex.substring(4, 6), 16);
            
            function adjust(delta) {
                const adj = c => Math.max(0, Math.min(255, c + delta));
                return `rgb(${adj(r)}, ${adj(g)}, ${adj(b)})`;
            }
            function rgba(a) {
                return `rgba(${r}, ${g}, ${b}, ${a})`;
            }
            
            document.documentElement.style.setProperty('--theme-color', color);
            document.documentElement.style.setProperty('--theme-light', adjust(60));
            document.documentElement.style.setProperty('--theme-dark', adjust(-40));
            document.documentElement.style.setProperty('--theme-gradient', `linear-gradient(135deg, ${color} 0%, ${rgba(0.9)} 50%, ${adjust(-60)} 100%)`);
            document.documentElement.style.setProperty('--theme-gradient-2', `linear-gradient(135deg, ${adjust(80)} 0%, ${color} 100%)`);
            document.documentElement.style.setProperty('--shadow-glow', `0 0 30px ${rgba(0.3)}`);
        } catch (e) {}
    }
    applyTheme();

    // 用户菜单点击显示（移动端）
    document.addEventListener('click', function (e) {
        const menus = document.querySelectorAll('.user-menu');
        menus.forEach(menu => {
            const avatar = menu.querySelector('.user-avatar');
            const dropdown = menu.querySelector('.dropdown-menu');
            if (!avatar || !dropdown) return;
            if (menu.contains(e.target)) {
                dropdown.classList.toggle('show');
            } else {
                dropdown.classList.remove('show');
            }
        });
    });

    // Hero 轮播
    const heroSlides = document.querySelectorAll('.hero-slide');
    const heroDots = document.querySelectorAll('.hero-dot');
    let heroIndex = 0;
    let heroTimer = null;
    function heroGoTo(idx) {
        if (!heroSlides.length) return;
        heroIndex = (idx + heroSlides.length) % heroSlides.length;
        heroSlides.forEach((s, i) => s.classList.toggle('active', i === heroIndex));
        heroDots.forEach((d, i) => d.classList.toggle('active', i === heroIndex));
    }
    function heroStart() {
        if (!heroSlides.length) return;
        heroStop();
        heroTimer = setInterval(() => heroGoTo(heroIndex + 1), 6000);
    }
    function heroStop() { if (heroTimer) { clearInterval(heroTimer); heroTimer = null; } }
    if (heroDots.length) {
        heroDots.forEach((d, i) => d.addEventListener('click', () => { heroGoTo(i); heroStart(); }));
        document.querySelector('.hero')?.addEventListener('mouseenter', heroStop);
        document.querySelector('.hero')?.addEventListener('mouseleave', heroStart);
    }
    heroStart();

    // 搜索提交
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const q = this.querySelector('input[name=q]').value.trim();
            if (q) window.location.href = 'search.php?q=' + encodeURIComponent(q);
        });
    }

    // Tab切换
    document.querySelectorAll('.tabs').forEach(tabs => {
        const btns = tabs.querySelectorAll('.tab-btn');
        const panes = document.querySelectorAll('.tab-pane');
        btns.forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.getAttribute('data-tab');
                btns.forEach(b => b.classList.toggle('active', b === btn));
                panes.forEach(p => p.classList.toggle('active', p.id === target));
            });
        });
    });

    // 发送验证码倒计时
    const sendBtns = document.querySelectorAll('.send-code-btn');
    sendBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const emailEl = document.querySelector(this.getAttribute('data-email'));
            const type = this.getAttribute('data-type') || 'register';
            const email = emailEl ? emailEl.value.trim() : '';
            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alert('请输入正确的邮箱地址');
                if (emailEl) emailEl.focus();
                return;
            }
            const origText = this.textContent;
            let count = 60;
            this.disabled = true;
            this.textContent = `${count}秒后重发`;
            fetch('api/send_code.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'email=' + encodeURIComponent(email) + '&type=' + encodeURIComponent(type)
            }).then(r => r.json()).then(res => {
                if (!res.success) alert(res.message || '发送失败');
            }).catch(() => {});
            const timer = setInterval(() => {
                count--;
                if (count <= 0) {
                    clearInterval(timer);
                    this.disabled = false;
                    this.textContent = origText;
                } else {
                    this.textContent = `${count}秒后重发`;
                }
            }, 1000);
        });
    });

    // 收藏切换
    document.querySelectorAll('[data-favorite]').forEach(btn => {
        btn.addEventListener('click', function () {
            const data = JSON.parse(this.getAttribute('data-favorite'));
            fetch('api/favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(data).toString()
            }).then(r => r.json()).then(res => {
                if (res.require_login) {
                    alert('需要登录才能收藏');
                    window.location.href = 'login.php';
                    return;
                }
                if (res.success) {
                    if (res.added) {
                        this.classList.add('btn-primary');
                        this.classList.remove('btn-outline');
                        this.querySelector('.fav-text') && (this.querySelector('.fav-text').textContent = '已收藏');
                    } else {
                        this.classList.remove('btn-primary');
                        this.classList.add('btn-outline');
                        this.querySelector('.fav-text') && (this.querySelector('.fav-text').textContent = '收藏');
                    }
                }
                toast(res.message, res.success ? 'success' : 'error');
            }).catch(() => toast('操作失败', 'error'));
        });
    });

    // 删除收藏/观看历史
    document.querySelectorAll('[data-delete]').forEach(btn => {
        btn.addEventListener('click', function () {
            if (!confirm('确认删除?')) return;
            const type = this.getAttribute('data-delete');
            const id = this.getAttribute('data-id');
            fetch('api/delete_' + type + '.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + id
            }).then(r => r.json()).then(res => {
                toast(res.message, res.success ? 'success' : 'error');
                if (res.success) setTimeout(() => location.reload(), 500);
            });
        });
    });

    // 点赞
    document.querySelectorAll('[data-like]').forEach(btn => {
        btn.addEventListener('click', function () {
            const fid = this.getAttribute('data-like');
            fetch('api/like_feedback.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'feedback_id=' + fid
            }).then(r => r.json()).then(res => {
                if (res.require_login) { location.href = 'login.php'; return; }
                const countEl = this.querySelector('.like-count');
                if (res.success) {
                    this.classList.toggle('liked', res.liked);
                    countEl.textContent = res.count;
                } else {
                    toast(res.message, 'error');
                }
            });
        });
    });

    // 展开回复
    document.querySelectorAll('.expand-replies-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const wrap = this.closest('.replies-section');
            const expanded = !wrap.classList.toggle('replies-collapsed');
            this.textContent = expanded ? '收起回复' : `展开全部回复 (${this.getAttribute('data-total') || 0}条)`;
        });
    });
    // 初始收缩超过3条的
    document.querySelectorAll('.replies-section').forEach(wrap => {
        const total = wrap.querySelectorAll('.reply-card').length;
        if (total > 3) {
            wrap.classList.add('replies-collapsed');
            const btn = wrap.querySelector('.expand-replies-btn');
            if (btn) btn.textContent = `展开全部回复 (${total}条)`;
        } else {
            const btn = wrap.querySelector('.expand-replies-btn');
            if (btn) btn.style.display = 'none';
        }
    });

    // Toast
    function toast(msg, type = 'info') {
        let box = document.getElementById('global-toast');
        if (!box) {
            box = document.createElement('div');
            box.id = 'global-toast';
            Object.assign(box.style, {
                position: 'fixed', top: '90px', left: '50%', transform: 'translateX(-50%)',
                zIndex: '99999', display: 'flex', flexDirection: 'column', gap: '8px',
                pointerEvents: 'none'
            });
            document.body.appendChild(box);
        }
        const el = document.createElement('div');
        const colors = {
            success: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
            error: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
            info: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
            warning: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)'
        };
        Object.assign(el.style, {
            background: colors[type] || colors.info,
            color: '#fff', padding: '12px 24px', borderRadius: '10px',
            fontSize: '14px', fontWeight: '600', boxShadow: '0 10px 30px rgba(0,0,0,0.4)',
            opacity: '0', transform: 'translateY(-10px)', transition: 'all .3s',
            minWidth: '200px', textAlign: 'center'
        });
        el.textContent = msg;
        box.appendChild(el);
        requestAnimationFrame(() => {
            el.style.opacity = '1'; el.style.transform = 'translateY(0)';
        });
        setTimeout(() => {
            el.style.opacity = '0'; el.style.transform = 'translateY(-10px)';
            setTimeout(() => el.remove(), 300);
        }, 2600);
    }
    window.toast = toast;

    // 公告关闭
    document.querySelectorAll('.announcement-modal').forEach(m => {
        m.querySelector('.modal-close')?.addEventListener('click', () => m.remove());
        const dismissBtn = m.querySelector('.ann-dismiss');
        if (dismissBtn) {
            dismissBtn.addEventListener('change', function () {
                if (this.checked) {
                    const id = this.getAttribute('data-id');
                    fetch('api/dismiss_announcement.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'announcement_id=' + id
                    });
                }
            });
        }
        const okBtn = m.querySelector('.ann-ok');
        if (okBtn) okBtn.addEventListener('click', () => m.remove());
    });

    // 确认对话框
    window.confirmDialog = function (msg) {
        return new Promise(resolve => {
            const overlay = document.createElement('div');
            overlay.className = 'modal-overlay';
            overlay.innerHTML = `
                <div class="modal" style="max-width:400px;">
                    <div class="modal-header"><h3>确认操作</h3></div>
                    <div class="modal-body" style="text-align:center; font-size:16px;">${msg}</div>
                    <div class="modal-footer" style="justify-content:center; gap:12px;">
                        <button class="btn btn-outline cancel-btn" style="padding:10px 24px;">取消</button>
                        <button class="btn btn-danger confirm-btn" style="padding:10px 24px;">确认</button>
                    </div>
                </div>
            `;
            document.body.appendChild(overlay);
            overlay.querySelector('.cancel-btn').onclick = () => { overlay.remove(); resolve(false); };
            overlay.querySelector('.confirm-btn').onclick = () => { overlay.remove(); resolve(true); };
            overlay.addEventListener('click', e => { if (e.target === overlay) { overlay.remove(); resolve(false); } });
        });
    };

    // 季切换
    document.querySelectorAll('[data-season]').forEach(tab => {
        tab.addEventListener('click', function () {
            const s = this.getAttribute('data-season');
            document.querySelectorAll('[data-season]').forEach(x => x.classList.toggle('active', x === this));
            document.querySelectorAll('[data-season-panel]').forEach(p => {
                p.style.display = p.getAttribute('data-season-panel') === s ? '' : 'none';
            });
        });
    });

    // 语言切换
    document.querySelectorAll('[data-lang]').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('[data-lang]').forEach(b => b.classList.toggle('active', b === this));
        });
    });

    // 播放源切换
    document.querySelectorAll('[data-source]').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('[data-source]').forEach(t => t.classList.toggle('active', t === this));
            const srcId = this.getAttribute('data-source');
            const list = document.querySelector('[data-sources-list]');
            if (list) {
                list.querySelectorAll('[data-source-id]').forEach(p => {
                    p.style.display = p.getAttribute('data-source-id') === srcId ? '' : 'none';
                });
            }
        });
    });

    // 观看时长记录（播放页）
    const playTracker = document.getElementById('playTracker');
    if (playTracker) {
        let seconds = parseInt(playTracker.getAttribute('data-seconds') || '0');
        const mediaId = playTracker.getAttribute('data-media-id');
        const mediaType = playTracker.getAttribute('data-media-type');
        const episode = playTracker.getAttribute('data-episode');
        setInterval(() => {
            seconds++;
            if (seconds % 10 === 0) {
                fetch('api/track_watch.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ media_id: mediaId, media_type: mediaType, episode: episode, seconds: seconds }).toString()
                }).catch(() => {});
            }
        }, 1000);
    }

    // 搜索自动跳转
    const searchQuick = document.getElementById('searchQuick');
    if (searchQuick) {
        searchQuick.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                const q = this.value.trim();
                if (q) window.location.href = 'search.php?q=' + encodeURIComponent(q);
            }
        });
    }

    // 反馈回复提交
    document.querySelectorAll('.reply-submit-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const wrap = this.closest('.reply-input-wrap');
            const input = wrap.querySelector('.reply-input');
            const fid = this.getAttribute('data-fid');
            const content = input.value.trim();
            if (!content) return;
            fetch('api/reply_feedback.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'feedback_id=' + fid + '&content=' + encodeURIComponent(content)
            }).then(r => r.json()).then(res => {
                if (res.require_login) { location.href = 'login.php'; return; }
                if (res.success) {
                    input.value = '';
                    setTimeout(() => location.reload(), 400);
                } else toast(res.message || '回复失败', 'error');
            });
        });
    });

    // 反馈提交
    const feedbackForm = document.getElementById('feedbackForm');
    if (feedbackForm) {
        feedbackForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const title = this.querySelector('[name=title]').value.trim();
            const content = this.querySelector('[name=content]').value.trim();
            if (!title || !content) { toast('请填写完整内容', 'warning'); return; }
            fetch('api/submit_feedback.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ title, content }).toString()
            }).then(r => r.json()).then(res => {
                if (res.require_login) { location.href = 'login.php'; return; }
                toast(res.message, res.success ? 'success' : 'error');
                if (res.success) setTimeout(() => location.reload(), 500);
            });
        });
    }

    // 头像上传预览
    const avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        avatarInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) { alert('图片大小不能超过2MB'); return; }
            const form = new FormData();
            form.append('avatar', file);
            fetch('api/upload_avatar.php', { method: 'POST', body: form })
                .then(r => r.json()).then(res => {
                    toast(res.message, res.success ? 'success' : 'error');
                    if (res.success && res.url) {
                        document.querySelectorAll('.profile-avatar, .user-avatar img').forEach(img => img.src = res.url);
                    }
                });
        });
    }

})();
