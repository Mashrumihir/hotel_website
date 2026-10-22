<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$requestUri  = $_SERVER['REQUEST_URI'] ?? '';
$isAuthPage  = in_array($currentPage, ['login.php', 'register.php', 'forgot-password.php']);
$isPortalPage = (strpos($requestUri, '/user/') !== false || strpos($requestUri, '/admin/') !== false);

if (!isset($hide_footer)) {
    $hide_footer = $isAuthPage || $isPortalPage;
}
?>
<?php if (!$hide_footer): ?>
    <!-- Footer -->
    <footer class="bg-deep-navy text-white pt-5 pb-4 border-top border-secondary selection:bg-primary">
        <div class="container">
            <div class="row g-4 pb-5 border-bottom border-secondary border-opacity-25">
                
                <!-- Column 1: Brand & Bio -->
                <div class="col-lg-5">
                    <a href="/hotel_website/index.php" class="d-inline-block text-decoration-none mb-3">
                        <img src="/hotel_website/uploads/logo.png" alt="Hotel Ease Logo" style="max-height: 70px; object-fit: contain;">
                    </a>
                    <p class="text-white-50 small pe-lg-4 mb-4 leading-relaxed">
                        <strong>HotelEase</strong> — <em>Luxury Stay. Effortless Experience.</em><br>
                        Discover world-class rooms, exquisite dining, 24/7 front desk concierge, and seamless online booking.
                    </p>
                    <div class="d-flex gap-2">
                        <a href="https://facebook.com" target="_blank" rel="noopener" class="footer-social-btn" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="https://instagram.com" target="_blank" rel="noopener" class="footer-social-btn" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        <a href="https://twitter.com" target="_blank" rel="noopener" class="footer-social-btn" title="X (Twitter)"><i class="fa-brands fa-x-twitter"></i></a>
                        <a href="https://linkedin.com" target="_blank" rel="noopener" class="footer-social-btn" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                    </div>
                </div>

                <!-- Column 2: Quick Links -->
                <div class="col-6 col-lg-3">
                    <h6 class="text-gold fw-bold uppercase tracking-wider mb-3" style="font-size: 0.85rem;">Quick Links</h6>
                    <ul class="list-unstyled small text-white-50 space-y-2">
                        <li class="mb-2"><a href="/hotel_website/index.php" class="text-white-50 text-decoration-none hover-white"><i class="fa-solid fa-chevron-right text-gold extra-small-label me-1.5"></i> Home</a></li>
                        <li class="mb-2"><a href="/hotel_website/index.php#rooms" class="text-white-50 text-decoration-none hover-white"><i class="fa-solid fa-chevron-right text-gold extra-small-label me-1.5"></i> Rooms</a></li>
                        <li class="mb-2"><a href="/hotel_website/index.php#quick-booking" class="text-white-50 text-decoration-none hover-white"><i class="fa-solid fa-chevron-right text-gold extra-small-label me-1.5"></i> Bookings</a></li>
                        <li class="mb-2"><a href="/hotel_website/index.php#about" class="text-white-50 text-decoration-none hover-white"><i class="fa-solid fa-chevron-right text-gold extra-small-label me-1.5"></i> About</a></li>
                        <li class="mb-2"><a href="/hotel_website/index.php#contact" class="text-white-50 text-decoration-none hover-white"><i class="fa-solid fa-chevron-right text-gold extra-small-label me-1.5"></i> Contact</a></li>
                    </ul>
                </div>

                <!-- Column 3: Support / Concierge -->
                <div class="col-6 col-lg-4">
                    <h6 class="text-gold fw-bold uppercase tracking-wider mb-3" style="font-size: 0.85rem;">Support</h6>
                    <p class="small text-white-50 mb-2 d-flex align-items-start gap-2">
                        <i class="fa-solid fa-location-dot text-gold mt-1"></i>
                        <span><?php echo SITE_ADDRESS; ?></span>
                    </p>
                    <p class="small text-white-50 mb-2 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-phone text-gold"></i>
                        <span><?php echo SITE_PHONE; ?></span>
                    </p>
                    <p class="small text-white-50 mb-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-envelope text-gold"></i>
                        <span><?php echo SITE_EMAIL; ?></span>
                    </p>
                </div>

            </div>

            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between pt-4 small text-white-50">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                <div class="d-flex gap-3 mt-2 mt-md-0">
                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#privacyPolicyModal" class="text-white-50 text-decoration-none hover-white">Privacy Policy</a>
                    <span>&bull;</span>
                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#termsModal" class="text-white-50 text-decoration-none hover-white">Terms &amp; Conditions</a>
                </div>
            </div>
        </div>
    </footer>
<?php endif; ?>

    <!-- Privacy Policy Modal -->
    <div class="modal fade text-navy" id="privacyPolicyModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title font-serif-title fw-bold text-navy"><i class="fa-solid fa-shield-halved text-primary me-2"></i>Privacy Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body small text-muted leading-relaxed">
                    <p class="mb-3">At HotelEase, we are committed to protecting your privacy. Your personal information, including contact details and payment records, is processed securely for reservation management and VIP concierge services.</p>
                    <h6 class="fw-bold text-navy">Information Collection</h6>
                    <p class="mb-3">We collect guest names, email addresses, phone numbers, and reservation preferences strictly to fulfill bookings and enhance your hospitality experience.</p>
                    <h6 class="fw-bold text-navy">Data Security</h6>
                    <p class="mb-0">All user passwords and credentials are encrypted using industry-standard hashing protocols. We never share or sell personal information to third parties.</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-primary btn-sm rounded-pill font-semibold px-4" data-bs-dismiss="modal">I Understand</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms & Conditions Modal -->
    <div class="modal fade text-navy" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title font-serif-title fw-bold text-navy"><i class="fa-solid fa-file-contract text-primary me-2"></i>Terms &amp; Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body small text-muted leading-relaxed">
                    <p class="mb-3">Welcome to HotelEase Luxury Stay. By accessing our portal or placing a reservation, you agree to comply with the following terms:</p>
                    <h6 class="fw-bold text-navy">Reservation &amp; Check-In</h6>
                    <p class="mb-3">Standard check-in time is 2:00 PM and check-out time is 11:00 AM. Government-issued photo identification is required upon arrival.</p>
                    <h6 class="fw-bold text-navy">Cancellation &amp; Refunds</h6>
                    <p class="mb-0">Cancellations processed prior to 24 hours of arrival are eligible for full refund according to booking category policy.</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-primary btn-sm rounded-pill font-semibold px-4" data-bs-dismiss="modal">Accept Terms</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
        const navbarCollapse = document.getElementById('navbarMain');

        navLinks.forEach(link => {
            link.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href && href.includes('#')) {
                    const targetId = href.substring(href.indexOf('#'));
                    const targetElement = document.querySelector(targetId);

                    if (targetElement) {
                        e.preventDefault();
                        
                        // Update active link state immediately on click
                        navLinks.forEach(nav => nav.classList.remove('active'));
                        this.classList.add('active');

                        // Smooth scroll with sticky navbar offset
                        const headerOffset = 80;
                        const elementPosition = targetElement.getBoundingClientRect().top;
                        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                        window.scrollTo({
                            top: offsetPosition,
                            behavior: 'smooth'
                        });

                        // Auto-collapse mobile navbar after item selection
                        if (navbarCollapse && navbarCollapse.classList.contains('show')) {
                            const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse) || new bootstrap.Collapse(navbarCollapse);
                            bsCollapse.hide();
                        }
                    }
                }
            });
        });

        // ScrollSpy: Automatically update active link on window scroll
        const sections = document.querySelectorAll('section[id]');
        window.addEventListener('scroll', function () {
            let currentScroll = window.pageYOffset + 110;
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.offsetHeight;
                const sectionId = section.getAttribute('id');
                const link = document.querySelector('.navbar-nav a[href*="#' + sectionId + '"]');

                if (link && currentScroll >= sectionTop && currentScroll < sectionTop + sectionHeight) {
                    navLinks.forEach(n => n.classList.remove('active'));
                    link.classList.add('active');
                }
            });
        });

        // Interactive Points & Lines Particle Canvas Animation
        const canvas = document.getElementById('particleCanvas');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            let width, height;
            let particles = [];
            const particleCount = 55;
            const maxDistance = 130;

            function resizeCanvas() {
                width = canvas.width = window.innerWidth;
                height = canvas.height = window.innerHeight;
            }

            class Particle {
                constructor() {
                    this.x = Math.random() * width;
                    this.y = Math.random() * height;
                    const speedX = Math.random() * 1.0 + 0.5;
                    const speedY = Math.random() * 1.0 + 0.5;
                    this.vx = Math.random() > 0.5 ? speedX : -speedX;
                    this.vy = Math.random() > 0.5 ? speedY : -speedY;
                    this.radius = Math.random() * 2.2 + 1;
                    this.color = Math.random() > 0.4 ? '201, 162, 39' : '56, 189, 248';
                }
                update() {
                    this.x += this.vx;
                    this.y += this.vy;
                    if (this.x < 0 || this.x > width) this.vx *= -1;
                    if (this.y < 0 || this.y > height) this.vy *= -1;
                }
                draw() {
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(${this.color}, 0.8)`;
                    ctx.fill();
                }
            }

            function initParticles() {
                resizeCanvas();
                particles = [];
                for (let i = 0; i < particleCount; i++) {
                    particles.push(new Particle());
                }
            }

            function animateParticles() {
                ctx.clearRect(0, 0, width, height);

                for (let i = 0; i < particles.length; i++) {
                    particles[i].update();
                    particles[i].draw();

                    for (let j = i + 1; j < particles.length; j++) {
                        const dx = particles[i].x - particles[j].x;
                        const dy = particles[i].y - particles[j].y;
                        const dist = Math.sqrt(dx * dx + dy * dy);

                        if (dist < maxDistance) {
                            const alpha = (1 - dist / maxDistance) * 0.4;
                            ctx.beginPath();
                            ctx.moveTo(particles[i].x, particles[i].y);
                            ctx.lineTo(particles[j].x, particles[j].y);
                            ctx.strokeStyle = `rgba(201, 162, 39, ${alpha})`;
                            ctx.lineWidth = 0.85;
                            ctx.stroke();
                        }
                    }
                }
                requestAnimationFrame(animateParticles);
            }

            window.addEventListener('resize', resizeCanvas);
            initParticles();
            animateParticles();
        }
    });

    // Global Password Show/Hide Toggle Function (Supports string ID or Element)
    function togglePass(inputId, iconIdOrBtnElem) {
        const input = typeof inputId === 'string' ? document.getElementById(inputId) : inputId;
        if (!input) return;
        
        let icon = null;
        if (typeof iconIdOrBtnElem === 'string') {
            icon = document.getElementById(iconIdOrBtnElem);
        } else if (iconIdOrBtnElem && iconIdOrBtnElem.querySelector) {
            icon = iconIdOrBtnElem.querySelector('i') || iconIdOrBtnElem;
        }
        if (!icon && input.parentElement) {
            icon = input.parentElement.querySelector('i.fa-eye, i.fa-eye-slash');
        }

        if (input.type === 'password') {
            input.type = 'text';
            if (icon && icon.classList) {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        } else {
            input.type = 'password';
            if (icon && icon.classList) {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    }
    window.togglePass = togglePass;
    window.togglePasswordVisibility = togglePass;

    // Global Red Form Validation Listener
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    });
    </script>

<!-- Interactive Room Photo Gallery & Zoom Modal -->
<div class="modal fade" id="roomPhotoZoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-bottom px-4 py-3 bg-white" style="background: var(--card-bg, #FFFFFF);">
                <div class="d-flex align-items-center gap-2">
                    <div class="badge bg-primary rounded-pill p-2">
                        <i class="fa-solid fa-camera text-white fs-6"></i>
                    </div>
                    <div>
                        <h5 class="modal-title font-serif-title fw-bold text-navy mb-0" id="modalRoomTitle">
                            Room Photos Showcase
                        </h5>
                        <p class="small text-muted mb-0" id="modalRoomSubtitle">
                            Interactive photo view with zoom & preview gallery
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white p-3">
                    <div class="row g-3">
                        <!-- Main Preview (Left) -->
                        <div class="col-md-8">
                            <div class="gallery-main-container">
                                <img id="modalMainGalleryImg" src="" alt="Featured Room View" onclick="openLightboxZoom(this.src)">
                                <span id="modalActiveCaption" class="position-absolute bottom-0 start-0 m-3 badge bg-dark bg-opacity-75 text-white px-3 py-2 rounded-pill small font-semibold shadow-sm">
                                    <i class="fa-solid fa-camera me-1.5 text-warning"></i> Featured Room View
                                </span>
                                <button type="button" onclick="openLightboxZoom(document.getElementById('modalMainGalleryImg').src)" class="position-absolute top-0 end-0 m-3 btn btn-light btn-sm rounded-circle p-2 shadow-sm d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; z-index: 5;" title="Zoom Fullscreen">
                                    <i class="fa-solid fa-expand text-navy"></i>
                                </button>
                            </div>
                        </div>
                        <!-- Thumbnails Grid (Right) -->
                        <div class="col-md-4">
                            <div class="row g-2" id="modalThumbnailsGrid">
                                <!-- Dynamic thumbnails generated via JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between align-items-center">
                <span class="small text-muted font-medium">
                    <i class="fa-solid fa-circle-info text-primary me-1"></i> Click any photo thumbnail to switch view, or click main image for full zoom view.
                </span>
                <button type="button" class="btn btn-secondary btn-sm px-4 rounded-pill font-semibold" data-bs-dismiss="modal">
                    Close Gallery
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Lightbox Zoom Fullscreen Overlay with Left/Right Navigation -->
<div id="lightboxZoomOverlay" onclick="closeLightboxZoom(event)">
    <button type="button" class="btn btn-light rounded-circle position-absolute top-0 end-0 m-4 p-2 shadow d-flex align-items-center justify-content-center" style="width: 46px; height: 46px; z-index: 1215;" onclick="closeLightboxZoom(event)" title="Close Fullscreen View">
        <i class="fa-solid fa-xmark fs-4 text-navy"></i>
    </button>
    
    <!-- Top Hint & Photo Counter Badge -->
    <div class="d-flex align-items-center gap-2 position-absolute top-0 start-50 translate-middle-x mt-4" style="z-index: 1215;">
        <span class="badge bg-dark bg-opacity-75 text-white px-3 py-2 rounded-pill small font-semibold shadow-sm border border-secondary border-opacity-25">
            <i class="fa-solid fa-magnifying-glass-plus me-1.5 text-warning"></i> Click image to toggle 1.5x Zoom
        </span>
        <span id="lightboxPhotoCounter" class="badge bg-primary px-3 py-2 rounded-pill small font-bold shadow-sm">
            Photo 1 / 4
        </span>
    </div>

    <!-- LEFT NAV ARROW (PREVIOUS) -->
    <button type="button" id="lightboxPrevBtn" class="lightbox-nav-btn nav-btn-left" onclick="navigateLightbox(-1, event)" title="Previous Photo (Left Arrow Key)">
        <i class="fa-solid fa-chevron-left fs-3"></i>
    </button>

    <!-- MAIN LIGHTBOX IMAGE -->
    <img id="lightboxZoomImage" src="" alt="Fullscreen Room Zoom" onclick="toggleLightboxScale(event)">

    <!-- RIGHT NAV ARROW (NEXT) -->
    <button type="button" id="lightboxNextBtn" class="lightbox-nav-btn nav-btn-right" onclick="navigateLightbox(1, event)" title="Next Photo (Right Arrow Key)">
        <i class="fa-solid fa-chevron-right fs-3"></i>
    </button>
</div>

<script>
// Global Room Photo Gallery Modal State
var currentGalleryPhotos = [];
var currentLightboxIndex = 0;
var defaultFallbackPhotos = [
    'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=800&q=80'
];

function openRoomGalleryModal(roomNo, roomType, mainImage, additionalPhotos) {
    const titleEl = document.getElementById('modalRoomTitle');
    const subtitleEl = document.getElementById('modalRoomSubtitle');
    const mainImgEl = document.getElementById('modalMainGalleryImg');
    const gridEl = document.getElementById('modalThumbnailsGrid');

    if (titleEl) titleEl.textContent = 'Room ' + roomNo + ' Gallery';
    if (subtitleEl) subtitleEl.textContent = (roomType || 'Luxury Suite') + ' • Interactive Zoom Showcase';

    // Build photo array
    let photos = [];
    if (mainImage && mainImage.trim() !== '') {
        photos.push(mainImage);
    }
    if (Array.isArray(additionalPhotos) && additionalPhotos.length > 0) {
        additionalPhotos.forEach(p => { if (p && !photos.includes(p)) photos.push(p); });
    }
    defaultFallbackPhotos.forEach(p => {
        if (photos.length < 4 && !photos.includes(p)) photos.push(p);
    });

    currentGalleryPhotos = photos;
    currentLightboxIndex = 0;

    if (mainImgEl && photos.length > 0) {
        mainImgEl.src = photos[0];
    }

    if (gridEl) {
        gridEl.innerHTML = '';
        const captions = ['Featured Room View', 'Private Balcony View', 'Luxury Bathroom', 'King Suite Interior'];
        photos.slice(0, 4).forEach((p, idx) => {
            const col = document.createElement('div');
            col.className = 'col-6';
            const isActive = idx === 0;
            col.innerHTML = `
                <div class="gallery-thumb-item ${isActive ? 'active-thumb' : ''}" style="height: 172px;" onclick="switchModalGalleryPhoto('${p.replace(/'/g, "\\'")}', this, '${captions[idx] || 'Room View'}', ${idx})">
                    <img src="${p}" alt="Photo ${idx+1}">
                    <div class="thumb-hover-overlay d-flex align-items-center justify-content-center text-white font-semibold small">
                        <i class="fa-solid fa-magnifying-glass-plus me-1"></i> View
                    </div>
                </div>
            `;
            gridEl.appendChild(col);
        });
    }

    const modalEl = document.getElementById('roomPhotoZoomModal');
    if (modalEl) {
        const bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();
    }
}

function switchModalGalleryPhoto(imgUrl, thumbEl, caption, index) {
    const mainImgEl = document.getElementById('modalMainGalleryImg');
    const captionEl = document.getElementById('modalActiveCaption');
    if (index !== undefined) {
        currentLightboxIndex = index;
    }
    if (mainImgEl) {
        mainImgEl.style.opacity = '0.3';
        setTimeout(() => {
            mainImgEl.src = imgUrl;
            mainImgEl.style.opacity = '1';
        }, 150);
    }
    if (captionEl && caption) {
        captionEl.innerHTML = '<i class="fa-solid fa-camera me-1.5 text-warning"></i> ' + caption;
    }
    document.querySelectorAll('.gallery-thumb-item').forEach(el => {
        el.classList.remove('active-thumb');
    });
    if (thumbEl) {
        thumbEl.classList.add('active-thumb');
    }
}

function openLightboxZoom(src) {
    const overlay = document.getElementById('lightboxZoomOverlay');
    const img = document.getElementById('lightboxZoomImage');
    if (!overlay || !img) return;

    if (currentGalleryPhotos.length === 0) {
        currentGalleryPhotos = defaultFallbackPhotos;
    }

    let foundIdx = currentGalleryPhotos.indexOf(src);
    if (foundIdx === -1) {
        foundIdx = 0;
        src = currentGalleryPhotos[0];
    }
    currentLightboxIndex = foundIdx;

    img.src = src;
    img.classList.remove('zoomed-in');
    updateLightboxUI();
    overlay.style.display = 'flex';
}

function navigateLightbox(direction, e) {
    if (e) e.stopPropagation();
    if (currentGalleryPhotos.length === 0) return;

    currentLightboxIndex += direction;
    if (currentLightboxIndex < 0) {
        currentLightboxIndex = currentGalleryPhotos.length - 1;
    } else if (currentLightboxIndex >= currentGalleryPhotos.length) {
        currentLightboxIndex = 0;
    }

    const img = document.getElementById('lightboxZoomImage');
    if (img) {
        img.style.opacity = '0.3';
        img.classList.remove('zoomed-in');
        setTimeout(() => {
            img.src = currentGalleryPhotos[currentLightboxIndex];
            img.style.opacity = '1';
        }, 120);
    }
    updateLightboxUI();
}

function updateLightboxUI() {
    const counterEl = document.getElementById('lightboxPhotoCounter');
    if (counterEl && currentGalleryPhotos.length > 0) {
        counterEl.textContent = 'Photo ' + (currentLightboxIndex + 1) + ' / ' + currentGalleryPhotos.length;
    }
}

function toggleLightboxScale(e) {
    if (e) e.stopPropagation();
    const img = document.getElementById('lightboxZoomImage');
    if (img) {
        img.classList.toggle('zoomed-in');
    }
}

function closeLightboxZoom(e) {
    if (e && (e.target.id === 'lightboxZoomImage' || e.target.closest('.lightbox-nav-btn'))) return;
    const overlay = document.getElementById('lightboxZoomOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// Keyboard Left / Right Arrow Navigation Listener
document.addEventListener('keydown', function(e) {
    const overlay = document.getElementById('lightboxZoomOverlay');
    if (overlay && overlay.style.display === 'flex') {
        if (e.key === 'ArrowLeft') {
            navigateLightbox(-1, e);
        } else if (e.key === 'ArrowRight') {
            navigateLightbox(1, e);
        } else if (e.key === 'Escape') {
            closeLightboxZoom(e);
        }
    }
});
</script>
</body>
</html>
