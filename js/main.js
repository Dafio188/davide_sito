// Main JS for Davide Fiore Portfolio

document.addEventListener('DOMContentLoaded', () => {

    // --- Cookie Consent Logic (Updated) ---
    const cookieBanner = document.getElementById('cookie-banner');
    const cookieAccept = document.getElementById('cookie-accept');
    const cookieReject = document.getElementById('cookie-reject');

    // Function to show banner (called after Intro or immediately if no intro)
    function showCookieBanner() {
        if (!localStorage.getItem('cookie_consent') && cookieBanner) {
            cookieBanner.classList.remove('hidden');
            cookieBanner.style.display = 'flex'; // Force flex
        }
    }

    const GA_ID = 'G-JXWG2WN80N'; // Davide, inserisci qui il tuo ID di Google Analytics

    // Function to activate GA
    function activateAnalytics() {
        // Notifica a Google il consenso (Consent Mode v2)
        gtag('consent', 'update', {
            'ad_storage': 'granted',
            'ad_user_data': 'granted',
            'ad_ads_personalization': 'granted',
            'analytics_storage': 'granted'
        });

        // Abilita il tracciamento del Meta Pixel (GDPR Compliant)
        if (typeof fbq === 'function') {
            fbq('consent', 'grant');
            console.log("🔵 Meta Pixel Consenso Accordato");
        }

        const gaScript = document.getElementById('ga-script');
        if (gaScript && gaScript.type === 'text/plain') {
            gaScript.type = 'text/javascript';
            // Re-inject the script to execute it
            const newScript = document.createElement('script');
            newScript.src = `https://www.googletagmanager.com/gtag/js?id=${GA_ID}`;
            newScript.async = true;
            document.head.appendChild(newScript);
            
            gtag('config', GA_ID);
            console.log("📈 Google Analytics & Consent Mode Attivati");
        }
    }

    if (cookieAccept) {
        cookieAccept.addEventListener('click', () => {
            localStorage.setItem('cookie_consent', 'accepted');
            activateAnalytics();
            hideCookieBanner();
        });
    }

    if (cookieReject) {
        cookieReject.addEventListener('click', () => {
            localStorage.setItem('cookie_consent', 'rejected');
            hideCookieBanner();
        });
    }

    // Check existing consent
    if (localStorage.getItem('cookie_consent') === 'accepted') {
        activateAnalytics();
    }

    function hideCookieBanner() {
        if (cookieBanner) {
            cookieBanner.classList.add('hidden');
            setTimeout(() => { cookieBanner.style.display = 'none'; }, 500);
        }
    }


    // Helper to track events
    window.trackEvent = (eventName, params = {}) => {
        if (localStorage.getItem('cookie_consent') === 'accepted' && typeof gtag === 'function') {
            gtag('event', eventName, params);
            console.log(`🎯 Evento tracciato: ${eventName}`, params);
        }
    };

    // DEBUG: Reset
    window.resetCookies = () => {
        localStorage.removeItem('cookie_consent');
        showCookieBanner();
        console.log("Cookies reset.");
    };

    // --- Reveal on Scroll Observer ---
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    const heroSection = document.querySelector('.hero');
    const cards = document.querySelectorAll('.card');

    if (heroSection) {
        observer.observe(heroSection);
        setTimeout(() => heroSection.classList.add('visible'), 100);
    }

    cards.forEach((card, index) => {
        // Init Blur Fade State
        card.classList.add('blur-fade-item');

        // Staggered Delay (0.25s initial + 0.1s per item)
        card.style.transitionDelay = `${250 + (index * 100)}ms`;

        observer.observe(card);
    });

    // --- Smooth Scroll ---
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // --- Contact Form ---
    const contactForm = document.getElementById('contact-form');
    // Find the wrapper card to apply the class to
    const contactCardWrapper = document.querySelector('.contact-card-form');

    window.submitContactForm = async (e) => {
        e.preventDefault();
        const btn = document.getElementById('form-submit-btn');
        const status = document.getElementById('form-status');
        const name = document.getElementById('form-name').value;
        const email = document.getElementById('form-email').value;
        const message = document.getElementById('form-message').value;
        const privacy = document.getElementById('form-privacy').checked;

        const b_website = document.querySelector('input[name="b_website"]').value;

        const originalText = btn.innerHTML;
        btn.innerHTML = '<span>Invio in corso...</span>';
        btn.disabled = true;

        // Hide legacy status if visible
        if (status) status.style.display = 'none';

        try {
            // Get reCAPTCHA token
            const recaptchaToken = await grecaptcha.execute('6Ld9XrIsAAAAAJffIUDG0gRTwEMARrwVJxsjANqa', { action: 'submit' });

            const res = await fetch('api/send_email_form.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, email, message, privacy, b_website, recaptcha_token: recaptchaToken })
            });
            const json = await res.json();

            if (json.success) {
                // Track Success
                window.trackEvent('generate_lead', {
                    method: 'contact_form',
                    content_type: 'consultancy'
                });

                // IDEA 4: MORPHING SUCCESS ANIMATION
                if (contactCardWrapper) {
                    contactCardWrapper.classList.add('success-state');
                } else {
                    // Fallback
                    if (status) {
                        status.style.display = 'block';
                        status.textContent = '✅ Messaggio inviato!';
                        status.style.color = '#2ecc71';
                    }
                }
                contactForm.reset();
            } else {
                if (status) {
                    status.style.display = 'block';
                    status.textContent = '❌ Errore: ' + (json.error || 'Riprova.');
                    status.style.color = '#e74c3c';
                }
            }
        } catch (err) {
            if (status) {
                status.style.display = 'block';
                status.textContent = '❌ Errore connessione.';
                status.style.color = '#e74c3c';
            }
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    };

    // --- Avatar Welcome ---
    const welcomeOverlay = document.getElementById('welcome-overlay');
    const enterBtn = document.getElementById('enter-site-btn');
    const heroAvatar = document.querySelector('.hero-avatar');
    const avatarVideo = document.getElementById('avatar-video');

    if (!sessionStorage.getItem('avatar_welcome_seen')) {
        if (welcomeOverlay) welcomeOverlay.style.display = 'flex';
    } else {
        // If already seen, show cookie banner immediately
        setTimeout(showCookieBanner, 1000);
    }

    if (avatarVideo) {
        avatarVideo.pause();
        avatarVideo.currentTime = 0;
    }

    if (enterBtn && welcomeOverlay) {
        enterBtn.addEventListener('click', () => {
            const btnSpan = enterBtn.querySelector('span');
            if (btnSpan) btnSpan.textContent = "Sto parlando...";
            enterBtn.style.opacity = "0.7";
            enterBtn.style.pointerEvents = "none";

            if (avatarVideo) {
                avatarVideo.play().then(() => {
                    avatarVideo.classList.add('playing'); // Start pulse animation
                }).catch(e => console.error("Video error:", e));

                avatarVideo.onended = () => {
                    avatarVideo.classList.remove('playing'); // Stop animation
                    setTimeout(finishWelcome, 1000);
                };
            }
            sessionStorage.setItem('avatar_welcome_seen', 'true');
        });
    }

    // SKIP INTRO LOGIC
    const skipBtn = document.getElementById('skip-intro-btn');
    if (skipBtn) {
        skipBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (avatarVideo) {
                avatarVideo.pause();
                avatarVideo.currentTime = 0;
                avatarVideo.classList.remove('playing');
            }
            sessionStorage.setItem('avatar_welcome_seen', 'true');
            finishWelcome();
        });
    }

    function finishWelcome() {
        if (welcomeOverlay && !welcomeOverlay.classList.contains('hidden')) {
            welcomeOverlay.classList.add('hidden');
            setTimeout(() => {
                welcomeOverlay.style.display = 'none';

                // SHOW COOKIE BANNER NOW
                showCookieBanner();

            }, 500);

            if (avatarVideo) {
                avatarVideo.pause();
                avatarVideo.currentTime = 0;
            }
            if (heroAvatar) heroAvatar.src = 'assets/avatar_new.png';
        }
    }
    // --- Performance Counter Micro-interaction ---
    const perfCard = document.querySelector('.bento-pos-2');
    const perfCounter = document.getElementById('perf-counter');
    let isAnimating = false;

    if (perfCard && perfCounter) {
        perfCard.addEventListener('mouseenter', () => {
            if (isAnimating) return;
            isAnimating = true;

            let start = 0;
            const end = 100;
            const duration = 800; // 0.8s
            const startTime = performance.now();

            function animate(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);

                // Ease out cubic
                const ease = 1 - Math.pow(1 - progress, 3);

                const val = Math.floor(start + (end - start) * ease);
                perfCounter.textContent = `Lighthouse ${val}/100`;

                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    isAnimating = false;
                }
            }

            requestAnimationFrame(animate);
        });
    }

    // ========================================
    // 📜 FASE 3: SCROLL EXPERIENCE PREMIUM
    // ========================================

    // 🚀 Scroll Progress Indicator
    const progressBar = document.createElement('div');
    progressBar.className = 'scroll-progress';
    document.body.prepend(progressBar);

    function updateScrollProgress() {
        const scrollTop = window.scrollY;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const scrollPercent = (scrollTop / docHeight) * 100;
        progressBar.style.width = `${scrollPercent}%`;
    }

    window.addEventListener('scroll', updateScrollProgress, { passive: true });

    // 📜 Auto-Hide Navbar on Scroll Down
    let lastScrollY = window.scrollY;
    let scrollTimeout;
    const navbar = document.querySelector('nav');

    function handleNavbarScroll() {
        const currentScrollY = window.scrollY;

        // Se sopra i 100px, sempre visibile
        if (currentScrollY < 100) {
            navbar.classList.remove('nav-hidden');
            navbar.classList.add('nav-visible');
        } else {
            // UPDATE: Navbar sempre visibile (Sticky) su richiesta utente
            // Rimosso logica di nascondimento allo scroll down
            navbar.classList.remove('nav-hidden');
            navbar.classList.add('nav-visible');
        }

        lastScrollY = currentScrollY;
    }

    window.addEventListener('scroll', () => {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(handleNavbarScroll, 10);
    }, { passive: true });

    // Inizializza navbar come visibile
    if (navbar) navbar.classList.add('nav-visible');

    // 🌟 Section Reveal Ottimizzato
    const sectionObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('section-visible');
                sectionObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px' // Fix: Removed negative margin to ensure visibility
    });

    // Osserva tutte le sezioni (tranne hero)
    document.querySelectorAll('section:not(.hero)').forEach(section => {
        sectionObserver.observe(section);
    });

    // ========================================
    // 💎 FASE 4: POLISH FINALE & PERFORMANCE
    // ========================================

    // 🎯 Lazy-Load Globo 3D (solo quando visibile)
    const globeSection = document.querySelector('.globe-section');
    if (globeSection) {
        const globeObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && typeof initGlobe === 'function') {
                    console.log('🌍 Inizializzo globo 3D...');
                    initGlobe();
                    globeObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });

        globeObserver.observe(globeSection);
    }

    // 🔥 Performance: Preload Critical Assets
    const preloadImages = [
        'assets/avatar_new.png',
        'assets/astroguida_preview.webp'
    ];

    preloadImages.forEach(src => {
        const link = document.createElement('link');
        link.rel = 'preload';
        link.as = 'image';
        link.href = src;
        document.head.appendChild(link);
    });

    // 🎯 Debug Helper: Reset tutto
    window.resetAll = () => {
        sessionStorage.clear();
        localStorage.clear();
        location.reload();
    };

    // --- Featured FruttaGest Gallery ---
    const fgThumbs = document.querySelectorAll('.featured-thumbnails .thumb');
    const fgMainImg = document.getElementById('fg-main-img');

    if (fgThumbs.length > 0 && fgMainImg) {
        fgThumbs.forEach(thumb => {
            thumb.addEventListener('click', function () {
                if (this.classList.contains('active')) return;

                const src = this.getAttribute('data-src');

                // Update active state
                fgThumbs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');


                // Animate image change
                fgMainImg.style.opacity = '0';
                fgMainImg.style.transform = 'scale(0.98)';

                setTimeout(() => {
                    fgMainImg.src = src;
                    fgMainImg.style.opacity = '1';
                    fgMainImg.style.transform = 'scale(1)';
                }, 300);
            });
        });
    }

    console.log('🚀 Premium Dark Mode Optimizations Loaded!');
    console.log('💡 Commands: resetCookies(), resetAll()');
});

