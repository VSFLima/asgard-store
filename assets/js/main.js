/**
 * Asgard Store - Main JavaScript
 * ========================================
 * Funcionalidades interativas do marketplace
 */

(function() {
    'use strict';

    // ============================================
    // INITIALIZATION
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        initFlashMessages();
        initMobileMenu();
        initSearch();
        initFavorites();
        initDropdowns();
        initSmoothScroll();
        initLazyLoading();
        initFormValidation();
        initTooltips();
        initCountdown();
        initImageGallery();
        initFilters();
        initInfiniteScroll();
        initBackToTop();
    });

    // ============================================
    // FLASH MESSAGES
    // ============================================
    function initFlashMessages() {
        document.querySelectorAll('.flash-message').forEach(function(msg) {
            setTimeout(function() {
                msg.style.opacity = '0';
                msg.style.transform = 'translateY(-10px)';
                setTimeout(function() { msg.remove(); }, 300);
            }, 5000);
            var closeBtn = msg.querySelector('.flash-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    msg.style.opacity = '0';
                    msg.style.transform = 'translateY(-10px)';
                    setTimeout(function() { msg.remove(); }, 300);
                });
            }
        });
    }

    // ============================================
    // MOBILE MENU
    // ============================================
    function initMobileMenu() {
        var toggle = document.querySelector('.mobile-menu-toggle');
        if (!toggle) return;
        toggle.addEventListener('click', function() {
            this.classList.toggle('active');
            document.querySelector('.nav-links').classList.toggle('show');
            document.querySelector('.nav-search').classList.toggle('show');
            document.querySelector('.nav-user').classList.toggle('show');
        });
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.navbar')) {
                toggle.classList.remove('active');
                document.querySelectorAll('.nav-links, .nav-search, .nav-user').forEach(function(el) {
                    if (el) el.classList.remove('show');
                });
            }
        });
    }

    // ============================================
    // SEARCH AUTOCOMPLETE (XSS-SAFE)
    // ============================================
    function initSearch() {
        var searchInput = document.querySelector('.search-input');
        if (!searchInput) return;
        var debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            var query = this.value.trim();
            if (query.length < 2) { hideSearchResults(); return; }
            debounceTimer = setTimeout(function() { fetchSearchResults(query); }, 300);
        });
        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 2) fetchSearchResults(this.value.trim());
        });
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.nav-search')) hideSearchResults();
        });
        function fetchSearchResults(query) {
            ajaxRequest('/api/public/busca.php?q=' + encodeURIComponent(query), 'GET', null, function(err, data) {
                if (err || !data || !data.success) return;
                showSearchResults(data.data);
            });
        }
        function showSearchResults(results) {
            hideSearchResults();
            if (!results || results.length === 0) return;
            var dropdown = document.createElement('div');
            dropdown.className = 'search-results';
            results.forEach(function(item) {
                var link = document.createElement('a');
                link.href = '/loja/anuncio.php?id=' + parseInt(item.id);
                link.className = 'search-result-item';
                var gameSpan = document.createElement('span');
                gameSpan.className = 'search-result-game';
                gameSpan.textContent = item.jogo_nome || '';
                var titleSpan = document.createElement('span');
                titleSpan.className = 'search-result-title';
                titleSpan.textContent = item.titulo || '';
                var priceSpan = document.createElement('span');
                priceSpan.className = 'search-result-price';
                priceSpan.textContent = 'R$ ' + parseFloat(item.preco || 0).toFixed(2);
                link.appendChild(gameSpan);
                link.appendChild(titleSpan);
                link.appendChild(priceSpan);
                dropdown.appendChild(link);
            });
            searchInput.parentElement.appendChild(dropdown);
        }
        function hideSearchResults() {
            var existing = document.querySelector('.search-results');
            if (existing) existing.remove();
        }
    }

    // ============================================
    // FAVORITES (AJAX)
    // ============================================
    function initFavorites() {
        document.querySelectorAll('.product-favorite, .btn-favorite').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var anuncioId = this.dataset.id;
                if (!anuncioId) return;
                var self = this;
                var icon = this.querySelector('i');
                var isActive = this.classList.contains('active');
                ajaxRequest('/painel/api/favorito.php', 'POST', {
                    anuncio_id: anuncioId,
                    action: isActive ? 'remove' : 'add'
                }, function(err, data) {
                    if (err || !data || !data.success) { showToast('Erro ao atualizar favorito', 'error'); return; }
                    self.classList.toggle('active');
                    icon.classList.toggle('far');
                    icon.classList.toggle('fas');
                    showToast(self.classList.contains('active') ? 'Adicionado aos favoritos' : 'Removido dos favoritos', self.classList.contains('active') ? 'success' : 'info');
                });
            });
        });
    }

    // ============================================
    // DROPDOWNS
    // ============================================
    function initDropdowns() {
        document.querySelectorAll('.user-dropdown').forEach(function(dropdown) {
            var btn = dropdown.querySelector('.user-btn');
            var menu = dropdown.querySelector('.dropdown-menu');
            if (btn && menu) {
                btn.addEventListener('click', function(e) { e.stopPropagation(); menu.classList.toggle('show'); });
                document.addEventListener('click', function(e) { if (!dropdown.contains(e.target)) menu.classList.remove('show'); });
            }
        });
    }

    // ============================================
    // SMOOTH SCROLL
    // ============================================
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                var target = document.querySelector(this.getAttribute('href'));
                if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
            });
        });
    }

    // ============================================
    // LAZY LOADING
    // ============================================
    function initLazyLoading() {
        if ('loading' in HTMLImageElement.prototype) return;
        var lazyImages = document.querySelectorAll('img[loading="lazy"]');
        if ('IntersectionObserver' in window) {
            var imageObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) { entry.target.src = entry.target.dataset.src || entry.target.src; imageObserver.unobserve(entry.target); }
                });
            });
            lazyImages.forEach(function(img) { imageObserver.observe(img); });
        }
    }

    // ============================================
    // FORM VALIDATION (Works on all forms)
    // ============================================
    function initFormValidation() {
        document.querySelectorAll('form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                var isValid = true;
                form.querySelectorAll('[required]').forEach(function(input) {
                    removeFieldError(input);
                    if (!input.value.trim()) { showFieldError(input, 'Este campo e obrigatorio'); isValid = false; }
                    else if (input.type === 'email' && !validateEmail(input.value)) { showFieldError(input, 'Email invalido'); isValid = false; }
                    else if (input.name === 'senha' && input.value.length < 6) { showFieldError(input, 'Minimo 6 caracteres'); isValid = false; }
                    else if (input.name === 'senha_confirm') {
                        var senha = form.querySelector('[name="senha"]');
                        if (senha && input.value !== senha.value) { showFieldError(input, 'Senhas nao conferem'); isValid = false; }
                    }
                });
                if (!isValid) { e.preventDefault(); e.stopPropagation(); }
            });
            form.querySelectorAll('input, select, textarea').forEach(function(input) {
                input.addEventListener('blur', function() {
                    if (this.hasAttribute('required') && !this.value.trim()) showFieldError(this, 'Este campo e obrigatorio');
                    else removeFieldError(this);
                });
                input.addEventListener('input', function() { removeFieldError(this); });
            });
        });
    }
    function showFieldError(input, message) {
        var group = input.closest('.form-group');
        if (!group) return;
        var existing = group.querySelector('.form-error');
        if (existing) existing.remove();
        input.classList.add('error');
        var error = document.createElement('span');
        error.className = 'form-error';
        error.textContent = message;
        group.appendChild(error);
    }
    function removeFieldError(input) {
        var group = input.closest('.form-group');
        if (!group) return;
        input.classList.remove('error');
        var error = group.querySelector('.form-error');
        if (error) error.remove();
    }

    // ============================================
    // TOOLTIPS
    // ============================================
    function initTooltips() {
        document.querySelectorAll('[data-tooltip]').forEach(function(el) {
            var tooltip = document.createElement('span');
            tooltip.className = 'tooltip';
            tooltip.textContent = el.dataset.tooltip;
            el.style.position = 'relative';
            el.appendChild(tooltip);
            el.addEventListener('mouseenter', function() { tooltip.classList.add('show'); });
            el.addEventListener('mouseleave', function() { tooltip.classList.remove('show'); });
        });
    }

    // ============================================
    // COUNTDOWN TIMER
    // ============================================
    function initCountdown() {
        document.querySelectorAll('[data-countdown]').forEach(function(el) {
            var target = new Date(el.dataset.countdown).getTime();
            var interval = setInterval(function() {
                var diff = target - new Date().getTime();
                if (diff <= 0) { clearInterval(interval); el.textContent = 'Expirado'; return; }
                var d = Math.floor(diff / 86400000); var h = Math.floor((diff % 86400000) / 3600000);
                var m = Math.floor((diff % 3600000) / 60000); var s = Math.floor((diff % 60000) / 1000);
                el.textContent = d + 'd ' + h + 'h ' + m + 'm ' + s + 's';
            }, 1000);
        });
    }

    // ============================================
    // IMAGE GALLERY
    // ============================================
    function initImageGallery() {
        var mainImage = document.querySelector('.gallery-main img');
        var thumbnails = document.querySelectorAll('.gallery-thumb');
        if (!mainImage || !thumbnails.length) return;
        thumbnails.forEach(function(thumb) {
            thumb.addEventListener('click', function() {
                mainImage.src = this.querySelector('img').src;
                thumbnails.forEach(function(t) { t.classList.remove('active'); });
                this.classList.add('active');
            });
        });
    }

    // ============================================
    // STORE FILTERS
    // ============================================
    function initFilters() {
        var filterForm = document.querySelector('#filter-form');
        if (!filterForm) return;
        filterForm.querySelectorAll('input[type="checkbox"], select').forEach(function(input) {
            input.addEventListener('change', function() { filterForm.submit(); });
        });
        var clearBtn = filterForm.querySelector('#clear-filters');
        if (clearBtn) {
            clearBtn.addEventListener('click', function(e) { e.preventDefault(); window.location.href = window.location.pathname; });
        }
    }

    // ============================================
    // INFINITE SCROLL
    // ============================================
    function initInfiniteScroll() {
        var loadMoreBtn = document.querySelector('#load-more-btn');
        var productsGrid = document.querySelector('.products-grid');
        if (!loadMoreBtn || !productsGrid) return;
        var page = 1, loading = false;
        loadMoreBtn.addEventListener('click', function() {
            if (loading) return;
            loading = true; page++;
            var url = new URL(window.location.href);
            url.searchParams.set('page', page);
            fetch(url.toString()).then(function(r) { return r.text(); }).then(function(html) {
                loading = false;
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newCards = doc.querySelectorAll('.product-card');
                newCards.forEach(function(card) { productsGrid.appendChild(card); });
                if (newCards.length === 0) loadMoreBtn.style.display = 'none';
            }).catch(function() { loading = false; });
        });
    }

    // ============================================
    // BACK TO TOP
    // ============================================
    function initBackToTop() {
        var btn = document.querySelector('#back-to-top');
        if (!btn) return;
        window.addEventListener('scroll', function() {
            btn.classList.toggle('show', window.scrollY > 300);
        });
        btn.addEventListener('click', function() { window.scrollTo({ top: 0, behavior: 'smooth' }); });
    }

    // ============================================
    // GLOBAL FUNCTIONS
    // ============================================
    window.togglePassword = function(btn) {
        var input = btn.previousElementSibling;
        var icon = btn.querySelector('i');
        input.type = input.type === 'password' ? 'text' : 'password';
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    };
    window.changeQuantity = function(input, delta) {
        var newVal = parseInt(input.value) + delta;
        if (newVal >= parseInt(input.min || 1) && newVal <= parseInt(input.max || 999)) input.value = newVal;
    };
    window.showToast = function(message, type) {
        type = type || 'info';
        var icons = { success: 'check-circle', error: 'exclamation-circle', warning: 'exclamation-triangle', info: 'info-circle' };
        var toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.innerHTML = '<i class="fas fa-' + icons[type] + '"></i> ';
        toast.appendChild(document.createTextNode(message));
        var container = document.querySelector('.toast-container');
        if (!container) { container = document.createElement('div'); container.className = 'toast-container'; document.body.appendChild(container); }
        container.appendChild(toast);
        setTimeout(function() { toast.classList.add('show'); }, 10);
        setTimeout(function() { toast.classList.remove('show'); setTimeout(function() { toast.remove(); }, 300); }, 3000);
    };
    window.ajaxRequest = function(url, method, data, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open(method, url, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) xhr.setRequestHeader('X-CSRF-Token', csrfMeta.content);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try { callback(null, JSON.parse(xhr.responseText)); }
                catch (e) { callback(e, null); }
            }
        };
        xhr.onerror = function() { callback(new Error('Network error'), null); };
        xhr.send(data ? JSON.stringify(data) : null);
    };
    window.validateEmail = function(email) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email); };
    
    window.toggleFavorite = function(btn) {
        var anuncioId = btn.dataset.id;
        if (!anuncioId) return;
        var isActive = btn.classList.contains('active');
        ajaxRequest('/painel/api/favorito.php', 'POST', {
            anuncio_id: anuncioId,
            action: isActive ? 'remove' : 'add'
        }, function(err, data) {
            if (err || !data || !data.success) { showToast('Erro ao atualizar favorito', 'error'); return; }
            btn.classList.toggle('active');
            var icon = btn.querySelector('i');
            icon.classList.toggle('far');
            icon.classList.toggle('fas');
            showToast(btn.classList.contains('active') ? 'Adicionado aos favoritos' : 'Removido dos favoritos', btn.classList.contains('active') ? 'success' : 'info');
        });
    };

    window.formatMoney = function(v) { return 'R$ ' + parseFloat(v).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };

})();
