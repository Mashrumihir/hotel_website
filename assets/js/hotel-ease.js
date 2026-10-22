/* ==========================================================================
   HOTEL EASE — JAVASCRIPT INTERACTIVE SYSTEM
   ========================================================================== */

document.addEventListener("DOMContentLoaded", function () {
    // Rotate through every configured homepage hero video instead of treating
    // multiple source tags as fallbacks for one video.
    const heroVideo = document.getElementById('heroBackgroundVideo');
    if (heroVideo) {
        let videoSources = [];
        try {
            videoSources = JSON.parse(heroVideo.getAttribute('data-video-sources') || '[]');
        } catch (error) {
            console.warn('Unable to read the hero video playlist.', error);
        }

        if (videoSources.length > 0) {
            let currentVideoIndex = 0;

            const playCurrentVideo = function () {
                if (videoSources.length === 0) return;
                heroVideo.src = videoSources[currentVideoIndex];
                heroVideo.load();
                const playPromise = heroVideo.play();
                if (playPromise && typeof playPromise.catch === 'function') {
                    playPromise.catch(() => {
                        // Browsers may block autoplay; the poster remains visible
                        // and playback can begin after a user interaction.
                    });
                }
            };

            heroVideo.addEventListener('ended', function () {
                currentVideoIndex = (currentVideoIndex + 1) % videoSources.length;
                playCurrentVideo();
            });

            heroVideo.addEventListener('error', function () {
                currentVideoIndex = (currentVideoIndex + 1) % videoSources.length;
                playCurrentVideo();
            });

            playCurrentVideo();
        }
    }

    // 1. Smooth Scrolling for Navigation Anchors
    const anchorLinks = document.querySelectorAll('a[href^="#"]:not([href="#"])');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (!targetId || targetId === '#') return;
            const targetEl = document.querySelector(targetId);
            if (targetEl) {
                e.preventDefault();
                const offset = 80;
                const bodyRect = document.body.getBoundingClientRect().top;
                const elementRect = targetEl.getBoundingClientRect().top;
                const elementPosition = elementRect - bodyRect;
                const offsetPosition = elementPosition - offset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });

    // 2. Animated Counter Numbers for Statistics
    const countElements = document.querySelectorAll('.counter-anim');
    if (countElements.length > 0 && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const targetVal = parseFloat(el.getAttribute('data-target') || '0');
                    const isDecimal = targetVal % 1 !== 0;
                    let count = 0;
                    const duration = 1200;
                    const steps = 40;
                    const stepVal = targetVal / steps;
                    const interval = duration / steps;

                    const timer = setInterval(() => {
                        count += stepVal;
                        if (count >= targetVal) {
                            count = targetVal;
                            clearInterval(timer);
                        }
                        el.textContent = isDecimal ? count.toFixed(1) : Math.floor(count);
                    }, interval);

                    obs.unobserve(el);
                }
            });
        }, { threshold: 0.5 });

        countElements.forEach(el => observer.observe(el));
    }

    // 3. Navbar Sticky Glass Scroll Modifier
    const mainNav = document.querySelector('.navbar-luxury');
    if (mainNav) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 40) {
                mainNav.classList.add('scrolled');
            } else {
                mainNav.classList.remove('scrolled');
            }
        });
    }

    // 4. Form Validation Auto Handler (Bootstrap 5 needs-validation)
    const needsValidationForms = document.querySelectorAll('.needs-validation');
    Array.from(needsValidationForms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // 5. Universal Accordion On-Click Handler for Smooth Toggle
    document.addEventListener('click', function (e) {
        const headerOrBtn = e.target.closest('.accordion-header, .accordion-button');
        if (!headerOrBtn) return;

        // Ignore clicks on nested interactive elements (links, forms, inputs, edit/delete buttons)
        if (e.target.closest('a, input, select, textarea, form, .btn-action-edit, .btn-action-delete, .badge-select')) {
            return;
        }

        const button = headerOrBtn.classList.contains('accordion-button')
            ? headerOrBtn
            : headerOrBtn.querySelector('.accordion-button');

        if (!button) return;

        const targetId = button.getAttribute('data-bs-target') || button.getAttribute('href');
        if (!targetId || !targetId.startsWith('#')) return;

        const targetCollapse = document.querySelector(targetId);
        if (!targetCollapse) return;

        // If click was on accordion-header outside the button, trigger collapse
        if (headerOrBtn.classList.contains('accordion-header') && e.target !== button && !button.contains(e.target)) {
            e.preventDefault();
            if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                const bsCollapse = bootstrap.Collapse.getInstance(targetCollapse) || new bootstrap.Collapse(targetCollapse, { toggle: false });
                bsCollapse.toggle();
            } else {
                const isShown = targetCollapse.classList.contains('show');
                if (isShown) {
                    targetCollapse.classList.remove('show');
                    button.classList.add('collapsed');
                    button.setAttribute('aria-expanded', 'false');
                } else {
                    targetCollapse.classList.add('show');
                    button.classList.remove('collapsed');
                    button.setAttribute('aria-expanded', 'true');
                }
            }
        }
    });
});


// 3. Global Dynamic Toast Notification Launcher
function showDynamicToast(message, type = 'success') {
    let container = document.getElementById('globalToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'globalToastContainer';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '10px';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    const isSuccess = type === 'success';
    const bg = isSuccess ? '#DCFCE7' : (type === 'warning' ? '#FEF3C7' : '#FEE2E2');
    const border = isSuccess ? '#86EFAC' : (type === 'warning' ? '#FDE68A' : '#FCA5A5');
    const color = isSuccess ? '#15803D' : (type === 'warning' ? '#B45309' : '#DC2626');
    const icon = isSuccess ? 'fa-circle-check' : (type === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-xmark');

    toast.style.background = bg;
    toast.style.border = '1px solid ' + border;
    toast.style.color = color;
    toast.style.padding = '12px 18px';
    toast.style.borderRadius = '12px';
    toast.style.fontSize = '0.9rem';
    toast.style.fontWeight = '600';
    toast.style.boxShadow = '0 6px 20px rgba(0,0,0,0.12)';
    toast.style.display = 'flex';
    toast.style.alignItems = 'center';
    toast.style.gap = '10px';
    toast.style.transition = 'all 0.3s ease';
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(-15px)';

    toast.innerHTML = `<i class="fa-solid ${icon}"></i> <span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    }, 20);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-15px)';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// 4. Global PDF Invoice Download Handler (html2pdf.js with print fallback)
function downloadInvoicePDF(containerId = 'printableUserInvoiceContent', filenamePrefix = 'Invoice') {
    const element = document.getElementById(containerId) || document.getElementById('printableUserInvoiceContent') || document.getElementById('printableInvoiceContent');
    if (!element) {
        window.print();
        return;
    }

    let refCode = 'HE-2026-0000';
    const refElem = document.getElementById('invRefNo');
    if (refElem && refElem.textContent) {
        refCode = refElem.textContent.trim().replace(/[^a-zA-Z0-9_-]/g, '');
    }

    if (typeof html2pdf !== 'undefined') {
        const opt = {
            margin:       [0.25, 0.25, 0.25, 0.25],
            filename:     `${filenamePrefix}_${refCode}.pdf`,
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true, logging: false },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
        };

        if (typeof showDynamicToast === 'function') {
            showDynamicToast('Generating PDF invoice download...', 'info');
        }

        html2pdf().set(opt).from(element).save().then(() => {
            if (typeof showDynamicToast === 'function') {
                showDynamicToast('PDF invoice downloaded successfully!', 'success');
            }
        }).catch(err => {
            console.error('HTML2PDF error, falling back to print:', err);
            window.print();
        });
    } else {
        window.print();
    }
}
