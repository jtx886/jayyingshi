(function(){
    'use strict';

    var FeedbackApp = {
        config: {
            apiUrl: 'api/feedback.php',
            likeSelector: '.like-btn',
            replyToggleSelector: '.reply-toggle',
            replySubmitSelector: '.reply-submit-btn',
            replyInputSelector: '.reply-input',
            expandRepliesSelector: '.expand-replies-btn',
            collapseRepliesSelector: '.collapse-replies-btn',
            feedbackFormSelector: '#feedbackForm',
            feedbackModalSelector: '#feedbackModal',
            loadMoreBtnSelector: '#loadMoreBtn',
            listSelector: '#feedbackList'
        },

        init: function(config) {
            if (config) {
                for (var key in config) {
                    if (config.hasOwnProperty(key)) {
                        this.config[key] = config[key];
                    }
                }
            }
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            document.body.addEventListener('click', function(e) {
                var likeBtn = e.target.closest(self.config.likeSelector);
                if (likeBtn) { self.toggleLike(likeBtn); return; }

                var replyToggle = e.target.closest(self.config.replyToggleSelector);
                if (replyToggle) { self.toggleReplies(replyToggle); return; }

                var replyBtn = e.target.closest(self.config.replySubmitSelector);
                if (replyBtn) { self.submitReply(replyBtn); return; }

                var expandBtn = e.target.closest(self.config.expandRepliesSelector);
                if (expandBtn) { self.expandReplies(expandBtn); return; }

                var collapseBtn = e.target.closest(self.config.collapseRepliesSelector);
                if (collapseBtn) { self.collapseReplies(collapseBtn); return; }
            });

            var form = document.querySelector(this.config.feedbackFormSelector);
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    self.submitFeedback(form);
                });
            }

            var loadMoreBtn = document.querySelector(this.config.loadMoreBtnSelector);
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function() {
                    self.loadMore(loadMoreBtn);
                });
            }

            var modal = document.querySelector(this.config.feedbackModalSelector);
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) modal.classList.remove('active');
                });
            }

            var charCount = document.getElementById('charCount');
            var feedbackContent = document.getElementById('feedbackContent');
            if (feedbackContent && charCount) {
                feedbackContent.addEventListener('input', function() {
                    charCount.textContent = this.value.length;
                });
            }
        },

        toggleLike: function(btn) {
            var self = this;
            var fid = btn.getAttribute('data-like');

            var formData = new FormData();
            formData.append('action', 'like');
            formData.append('feedback_id', fid);

            this.fetchRequest(formData)
                .then(function(d) {
                    if (d.code === 200) {
                        var countEl = btn.querySelector('.like-count');
                        btn.classList.toggle('liked', d.liked);
                        var currentCount = parseInt(countEl.textContent) || 0;
                        countEl.textContent = d.liked ? currentCount + 1 : Math.max(0, currentCount - 1);

                        if (d.liked) {
                            self.playLikeAnimation(btn);
                            self.showToast('点赞成功', 'success');
                        } else {
                            self.showToast('已取消点赞', 'info');
                        }
                    } else {
                        self.showToast(d.message || '操作失败', 'error');
                    }
                })
                .catch(function() {
                    self.showToast('网络错误', 'error');
                });
        },

        playLikeAnimation: function(btn) {
            var pop = document.createElement('span');
            pop.className = 'like-pop';
            pop.textContent = '\u2665';
            btn.appendChild(pop);
            setTimeout(function(){ pop.remove(); }, 500);
        },

        toggleReplies: function(btn) {
            var fid = btn.getAttribute('data-reply-toggle');
            var section = document.getElementById('replies-' + fid);
            if (!section) return;

            section.classList.toggle('active');

            if (section.classList.contains('active')) {
                var input = section.querySelector(this.config.replyInputSelector);
                if (input) {
                    setTimeout(function(){ input.focus(); }, 100);
                }
            }
        },

        submitReply: function(btn) {
            var self = this;
            var fid = btn.getAttribute('data-fid');
            var card = btn.closest('.feedback-card');
            var input = card.querySelector(this.config.replyInputSelector);
            var content = input.value.trim();

            if (!content) {
                this.showToast('请输入回复内容', 'error');
                input.focus();
                return;
            }

            var origHTML = btn.innerHTML;
            btn.disabled = true;
            btn.textContent = '发送中...';

            var formData = new FormData();
            formData.append('action', 'reply');
            formData.append('feedback_id', fid);
            formData.append('content', content);

            this.fetchRequest(formData)
                .then(function(d) {
                    if (d.code === 200) {
                        self.showToast('回复成功', 'success');
                        input.value = '';
                        setTimeout(function(){ location.reload(); }, 600);
                    } else {
                        self.showToast(d.message || '回复失败', 'error');
                        btn.disabled = false;
                        btn.innerHTML = origHTML;
                    }
                })
                .catch(function() {
                    self.showToast('网络错误', 'error');
                    btn.disabled = false;
                    btn.innerHTML = origHTML;
                });
        },

        expandReplies: function(btn) {
            var fid = btn.getAttribute('data-fid');
            var section = document.getElementById('replies-' + fid);
            if (!section) return;

            section.classList.remove('collapsed-replies');
            btn.style.display = 'none';
            var collapseBtn = section.querySelector(this.config.collapseRepliesSelector);
            if (collapseBtn) collapseBtn.style.display = 'block';
        },

        collapseReplies: function(btn) {
            var fid = btn.getAttribute('data-fid');
            var section = document.getElementById('replies-' + fid);
            if (!section) return;

            section.classList.add('collapsed-replies');
            btn.style.display = 'none';
            var expandBtn = section.querySelector(this.config.expandRepliesSelector);
            if (expandBtn) expandBtn.style.display = 'block';
        },

        submitFeedback: function(form) {
            var self = this;
            var content = form.querySelector('#feedbackContent').value.trim();

            if (!content) {
                this.showToast('请输入反馈内容', 'error');
                return;
            }

            var submitBtn = document.getElementById('submitFeedbackBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = '提交中...';

            var formData = new FormData();
            formData.append('action', 'create');
            formData.append('content', content);

            this.fetchRequest(formData)
                .then(function(d) {
                    if (d.code === 200) {
                        self.showToast('反馈提交成功', 'success');
                        var modal = document.querySelector(self.config.feedbackModalSelector);
                        if (modal) modal.classList.remove('active');
                        form.reset();
                        var charCount = document.getElementById('charCount');
                        if (charCount) charCount.textContent = '0';
                        setTimeout(function(){ location.reload(); }, 800);
                    } else {
                        self.showToast(d.message || '提交失败', 'error');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<span class="icon-svg icon-send"></span>提交反馈';
                    }
                })
                .catch(function() {
                    self.showToast('网络错误，请重试', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span class="icon-svg icon-send"></span>提交反馈';
                });
        },

        loadMore: function(btn) {
            var self = this;
            var currentPage = parseInt(btn.getAttribute('data-page'));
            var totalPages = parseInt(btn.getAttribute('data-total'));
            var nextPage = currentPage + 1;

            if (nextPage > totalPages) {
                this.showToast('已经是最后一页了', 'info');
                return;
            }

            btn.textContent = '加载中...';
            btn.disabled = true;

            fetch('feedback.php?page=' + nextPage, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newCards = doc.querySelectorAll('.feedback-card');
                var list = document.querySelector(self.config.listSelector);

                if (list && newCards.length > 0) {
                    for (var i = 0; i < newCards.length; i++) {
                        list.appendChild(newCards[i]);
                    }
                    self.showToast('加载成功', 'success');
                }

                btn.setAttribute('data-page', nextPage);
                if (nextPage >= totalPages) {
                    btn.style.display = 'none';
                } else {
                    btn.textContent = '加载更多';
                    btn.disabled = false;
                }
            })
            .catch(function() {
                self.showToast('加载失败', 'error');
                btn.textContent = '加载更多';
                btn.disabled = false;
            });
        },

        fetchRequest: function(formData) {
            return fetch(this.config.apiUrl, {
                method: 'POST',
                body: formData
            }).then(function(r) { return r.json(); });
        },

        showToast: function(msg, type) {
            var toast = document.getElementById('toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'toast';
                toast.className = 'toast';
                document.body.appendChild(toast);
            }
            toast.textContent = msg;
            toast.className = 'toast show' + (type ? ' ' + type : '');
            setTimeout(function(){ toast.classList.remove('show'); }, 2200);
        }
    };

    window.FeedbackApp = FeedbackApp;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function(){ FeedbackApp.init(); });
    } else {
        FeedbackApp.init();
    }

})();