/**
 * PREMIUM EFFECTS SUITE
 * 1. Lenis Smooth Scroll (Inertial Scrolling)
 * 2. Neural Network Background (Non-Home Pages only)
 * 3. Scrollytelling Text Reveal (Content Pages only)
 */

/* --- 1. LENIS SMOOTH SCROLL --- */
// Minified bundle included or CDN link required. 
// For simplicity in this env, we assume we load Lenis from CDN in HTML, 
// or if not possible, we use a lightweight custom vanilla implementation.
// Let's use a robust CDN approach in the HTML, and initialize here.

function initSmoothScroll() {
    // 🚫 LENIS DISABLED BY USER REQUEST (Reverting to native scroll)
    return;

    /*
    if (typeof Lenis !== 'undefined') {
        const lenis = new Lenis({
            duration: 1.2,
            easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
            direction: 'vertical',
            gestureDirection: 'vertical',
            smooth: true,
            mouseMultiplier: 1,
            smoothTouch: false,
            touchMultiplier: 2,
        });

        // Sync with standard requestAnimationFrame
        function raf(time) {
            lenis.raf(time);
            requestAnimationFrame(raf);
        }
        requestAnimationFrame(raf);

        // Connect standard anchors to Lenis
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                lenis.scrollTo(this.getAttribute('href'));
            });
        });

        console.log("🚀 Lenis Smooth Scroll Activated");
    } else {
        console.warn("⚠️ Lenis library not found. Falling back to native scroll.");
    }
    */
}


/* --- 2. CANVAS NEURAL NETWORK --- */
class NeuralNetwork {
    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) return;

        this.ctx = this.canvas.getContext('2d');
        this.width = window.innerWidth;
        this.height = window.innerHeight;
        this.particles = [];
        // Config: Less particles for performance
        this.config = {
            count: window.innerWidth < 768 ? 40 : 80,
            linkDist: 150,
            speed: 0.5
        };

        this.init();
        this.animate();
    }

    init() {
        this.resize();
        window.addEventListener('resize', () => this.resize());

        // Create particles
        for (let i = 0; i < this.config.count; i++) {
            this.particles.push({
                x: Math.random() * this.width,
                y: Math.random() * this.height,
                vx: (Math.random() - 0.5) * this.config.speed,
                vy: (Math.random() - 0.5) * this.config.speed,
                size: Math.random() * 2 + 1
            });
        }
    }

    resize() {
        this.width = window.innerWidth;
        this.height = window.innerHeight;
        this.canvas.width = this.width;
        this.canvas.height = this.height;
    }

    animate() {
        this.ctx.clearRect(0, 0, this.width, this.height);

        // Update & Draw Particles
        for (let i = 0; i < this.particles.length; i++) {
            let p = this.particles[i];

            // Move
            p.x += p.vx;
            p.y += p.vy;

            // Bounce
            if (p.x < 0 || p.x > this.width) p.vx *= -1;
            if (p.y < 0 || p.y > this.height) p.vy *= -1;

            // Draw Dot
            this.ctx.fillStyle = "rgba(139, 92, 246, 0.5)"; // Violet
            this.ctx.beginPath();
            this.ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
            this.ctx.fill();

            // Link Lines
            for (let j = i + 1; j < this.particles.length; j++) {
                let p2 = this.particles[j];
                let dist = Math.hypot(p.x - p2.x, p.y - p2.y);

                if (dist < this.config.linkDist) {
                    this.ctx.strokeStyle = `rgba(139, 92, 246, ${1 - dist / this.config.linkDist})`;
                    this.ctx.lineWidth = 0.5;
                    this.ctx.beginPath();
                    this.ctx.moveTo(p.x, p.y);
                    this.ctx.lineTo(p2.x, p2.y);
                    this.ctx.stroke();
                }
            }
        }

        requestAnimationFrame(() => this.animate());
    }
}

/* --- 3. SCROLLYTELLING TEXT REVEAL --- */
function initTextReveal() {
    const content = document.querySelector('.page-content');
    if (!content) return; // Only run on internal pages

    // CSS Injection for the effect
    const style = document.createElement('style');
    style.innerHTML = `
        .reveal-text {
            opacity: 0.2;
            transition: opacity 0.6s ease, transform 0.6s ease;
            transform: translateX(-10px);
            will-change: opacity, transform;
        }
        .reveal-text.active {
            opacity: 1;
            transform: translateX(0);
        }
    `;
    document.head.appendChild(style);

    // Target elements: paragraphs and list items inside page-content
    const targets = content.querySelectorAll('p, li, h2, h3, .feature-card');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
            } else {
                // Optional: remove active to make it fade out again when scrolling up
                entry.target.classList.remove('active');
            }
        });
    }, {
        root: null,
        rootMargin: '-10% 0px -10% 0px', // Trigger when element is central(ish)
        threshold: 0.1
    });

    targets.forEach(el => {
        el.classList.add('reveal-text');
        observer.observe(el);
    });

    console.log(`👁️ Text Reveal active on ${targets.length} elements`);
}


/* --- INITIALIZATION --- */
document.addEventListener('DOMContentLoaded', () => {

    // 1. Smooth Scroll (Global)
    initSmoothScroll();

    // 2. Text Reveal (Internal Pages only)
    initTextReveal();

    // 3. Neural Background (Internal Pages only)
    // We check if we are NOT on home page (simple check: is there a .hero-card?)
    // Or simpler: check if a specific canvas ID exists that we will add only to internal pages
    if (document.getElementById('neural-canvas')) {
        new NeuralNetwork('neural-canvas');
        console.log("🧠 Neural Network Background Active");
    }

});
