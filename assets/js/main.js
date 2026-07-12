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
            // Auto-close after 5 seconds
            setTimeout(function() {
                msg.style.opacity = '0';
                msg.style.transform = 'translateY(-10px)';
                setTimeout(function() { msg.remove(); }, 300);
            }, 5000);

            // Close button
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
        var navLinks = document.querySelector('.nav-links');
        var navSearch = document.querySelector('.nav-search');
        var navUser = document.querySelector('.nav-user');

        if (toggle) {
            toggle.addEventListener('click', function() {
                this.classList.toggle('active');
                if (navLinks) navLinks.classList.toggle('show');
                if (navSearch) navSearch.classList.toggle('show');
                if (navUser) navUser.classList.toggle('show');
            });

            // Close on outside click
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.navbar')) {
                    toggle.classList.remove('active');
                    if (navLinks) navLinks.classList.remove('show');
                    if (navSearch) navSearch.classList.remove('show');
                    if (navUser) navUser.classList.remove('show');
                }
            });
        }
    }

    // ============================================
    // SEARCH AUTOCOMPLETE
    // ============================================
    function initSearch() {
        var searchInput = document.querySelector('.search-input');
        if (!searchInput) return;

        var searchForm = searchInput.closest('form');
        var debounceTimer;

        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            var query = this.value.trim();

            if (query.length < 2) {
                hideSearchResults();
                return;
            }

            debounceTimer = setTimeout(function() {
                fetchSearchResults(query);
            }, 300);
        });

        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 2) {
                fetchSearchResults(this.value.trim());
            }
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.nav-search')) {
                hideSearchResults();
            }
        });

        function fetchSearchResults(query) {
            ajaxRequest('/api/public/busca.php?q=' + encodeURIComponent(query), 'GET', null, function(err, data) {
                if (err || !data || !data.success) return;
                showSearchResults(data.data);
            });
        }

        function showSearchResults(results) {
            var existing = document.querySelector('.search-results');
            if (existing) existing.remove();

            if (!results || results.length === 0) return;

            var dropdown = document.createElement('div');
            dropdown.className = 'search-results';
            results.forEach(function(item) {
                var link = document.createElement('a');
                link.href = item.url || '/loja/anuncio.php?id=' + item.id;
                link.className = 'search-result-item';
                link.innerHTML = '<span class="search-result-game">' + (item.jogo_nome || '') + '</span>' +
                    '<span class="search-result-title">' + item.titulo + '</span>' +
                    '<span class="search-result-price">R$ ' + parseFloat(item.preco).toFixed(2) + '</span>';
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
        document.querySelectorAll('.product-favorite').forEach(function(btn) {
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
                    if (err || !data || !data.success) {
                        showToast('Erro ao atualizar favorito', 'error');
                        return;
                    }

                    self.classList.toggle('active');
                    if (self.classList.contains('active')) {
                        icon.classList.replace('far', 'fas');
                        showToast('Adicionado aos favoritos', 'success');
                    } else {
                        icon.classList.replace('fas', 'far');
                        showToast('Removido dos favoritos', 'info');
                    }
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
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    menu.classList.toggle('show');
                });

                document.addEventListener('click', function(e) {
                    if (!dropdown.contains(e.target)) {
                        menu.classList.remove('show');
                    }
                });
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
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
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
                    if (entry.isIntersecting) {
                        var img = entry.target;
                        img.src = img.dataset.src || img.src;
                        imageObserver.unobserve(img);
                    }
                });
            });
            lazyImages.forEach(function(img) { imageObserver.observe(img); });
        }
    }

    // ============================================
    // FORM VALIDATION
    // ============================================
    function initFormValidation() {
        document.querySelectorAll('form[data-validate]').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                var isValid = true;

                form.querySelectorAll('[required]').forEach(function(input) {
                    removeFieldError(input);

                    if (!input.value.trim()) {
                        showFieldError(input, 'Este campo e obrigatorio');
                        isValid = false;
                    } else if (input.type === 'email' && !validateEmail(input.value)) {
                        showFieldError(input, 'Email invalido');
                        isValid = false;
                    } else if (input.name === 'senha' && input.value.length < 6) {
                        showFieldError(input, 'Minimo 6 caracteres');
                        isValid = false;
                    } else if (input.name === 'senha_confirm') {
                        var senha = form.querySelector('[name="senha"]');
                        if (senha && input.value !== senha.value) {
                            showFieldError(input, 'Senhas nao conferem');
                            isValid = false;
                        }
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });

            // Real-time validation
            form.querySelectorAll('input, select, textarea').forEach(function(input) {
                input.addEventListener('blur', function() {
                    if (this.hasAttribute('required') && !this.value.trim()) {
                        showFieldError(this, 'Este campo e obrigatorio');
                    } else {
                        removeFieldError(this);
                    }
                });

                input.addEventListener('input', function() {
                    removeFieldError(this);
                });
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

            el.addEventListener('mouseenter', function() {
                tooltip.classList.add('show');
            });

            el.addEventListener('mouseleave', function() {
                tooltip.classList.remove('show');
            });
        });
    }

    // ============================================
    // COUNTDOWN TIMER
    // ============================================
    function initCountdown() {
        document.querySelectorAll('[data-countdown]').forEach(function(el) {
            var target = new Date(el.dataset.countdown).getTime();

            var interval = setInterval(function() {
                var now = new Date().getTime();
                var diff = target - now;

                if (diff <= 0) {
                    clearInterval(interval);
                    el.textContent = 'Expirado';
                    return;
                }

                var days = Math.floor(diff / (1000 * 60 * 60 * 24));
                var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((diff % (1000 * 60)) / 1000);

                el.textContent = days + 'd ' + hours + 'h ' + minutes + 'm ' + seconds + 's';
            }, 1000);
        });
    }

    // ============================================
    // IMAGE GALLERY (Product Detail)
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

        // Auto-submit on filter change
        filterForm.querySelectorAll('input[type="checkbox"], select').forEach(function(input) {
            input.addEventListener('change', function() {
                filterForm.submit();
            });
        });

        // Price range
        var priceMin = filterForm.querySelector('#price-min');
        var priceMax = filterForm.querySelector('#price-max');
        var priceSlider = filterForm.querySelector('#price-slider');

        if (priceSlider) {
            priceSlider.addEventListener('input', function() {
                priceMax.value = this.value;
            });
        }

        // Clear filters
        var clearBtn = filterForm.querySelector('#clear-filters');
        if (clearBtn) {
            clearBtn.addEventListener('click', function(e) {
                e.preventDefault();
                filterForm.reset();
                window.location.href = window.location.pathname;
            });
        }

        // Active filters display
        var activeFilters = document.querySelector('.active-filters');
        if (activeFilters) {
            activeFilters.querySelectorAll('.remove-filter').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var param = this.dataset.param;
                    var url = new URL(window.location);
                    url.searchParams.delete(param);
                    window.location.href = url.toString();
                });
            });
        }
    }

    // ============================================
    // INFINITE SCROLL (Store)
    // ============================================
    function initInfiniteScroll() {
        var loadMoreBtn = document.querySelector('#load-more-btn');
        var productsGrid = document.querySelector('.products-grid');

        if (!loadMoreBtn || !productsGrid) return;

        var page = 1;
        var loading = false;

        loadMoreBtn.addEventListener('click', function() {
            if (loading) return;
            loading = true;
            page++;

            var url = new URL(window.location);
            url.searchParams.set('page', page);

            ajaxRequest(url.toString(), 'GET', null, function(err, data) {
                loading = false;
                if (err || !data || !data.success) return;

                if (data.data.html) {
                    productsGrid.insertAdjacentHTML('beforeend', data.data.html);
                }

                if (!data.data.has_next) {
                    loadMoreBtn.style.display = 'none';
                }
            });
        });
    }

    // ============================================
    // BACK TO TOP
    // ============================================
    function initBackToTop() {
        var btn = document.querySelector('#back-to-top');
        if (!btn) return;

        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                btn.classList.add('show');
            } else {
                btn.classList.remove('show');
            }
        });

        btn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ============================================
    // PASSWORD TOGGLE
    // ============================================
    window.togglePassword = function(btn) {
        var input = btn.previousElementSibling;
        var icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    };

    // ============================================
    // QUANTITY SELECTOR
    // ============================================
    window.changeQuantity = function(input, delta) {
        var newVal = parseInt(input.value) + delta;
        if (newVal >= parseInt(input.min) && newVal <= parseInt(input.max)) {
            input.value = newVal;
            input.dispatchEvent(new Event('change'));
        }
    };

    // ============================================
    // TOAST NOTIFICATIONS
    // ============================================
    window.showToast = function(message, type) {
        type = type || 'info';
        var toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.innerHTML = '<i class="fas fa-' + getToastIcon(type) + '"></i> ' + message;

        var container = document.querySelector('.toast-container') || createToastContainer();
        container.appendChild(toast);

        setTimeout(function() {
            toast.classList.add('show');
        }, 10);

        setTimeout(function() {
            toast.classList.remove('show');
            setTimeout(function() { toast.remove(); }, 300);
        }, 3000);
    };

    function createToastContainer() {
        var container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
        return container;
    }

    function getToastIcon(type) {
        var icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    // ============================================
    // AJAX HELPER
    // ============================================
    window.ajaxRequest = function(url, method, data, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open(method, url, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
            xhr.setRequestHeader('X-CSRF-Token', csrfMeta.content);
        }

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    callback(null, response);
                } catch (e) {
                    callback(e, null);
                }
            }
        };

        xhr.onerror = function() {
            callback(new Error('Network error'), null);
        };

        xhr.send(data ? JSON.stringify(data) : null);
    };

    // ============================================
    // UTILITY FUNCTIONS
    // ============================================
    window.validateEmail = function(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    };

    window.validatePhone = function(phone) {
        return /^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$/.test(phone);
    };

    window.formatMoney = function(value) {
        return 'R$ ' + parseFloat(value).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    };

    window.getCookie = function(name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    };

    window.debounce = function(func, wait) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    };

})();
