// Main JS for Davide Fiore Portfolio

document.addEventListener('DOMContentLoaded', () => {

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
                observer.unobserve(entry.target); // Only animate once
            }
        });
    }, observerOptions);

    // Elements to animate
    const heroSection = document.querySelector('.hero');
    const cards = document.querySelectorAll('.card');

    if (heroSection) {
        observer.observe(heroSection);
        // Instant reveal if at top (sometimes observer is slow on load)
        setTimeout(() => heroSection.classList.add('visible'), 100);
    }

    cards.forEach((card, index) => {
        // Stagger animations slightly
        card.style.transitionDelay = `${index * 100}ms`;
        observer.observe(card);
    });

    // --- Smooth Anchor Scrolling (Polyfill-ish logic for older browsers or complex needs) ---
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);

            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // --- Contact Form Logic (AJAX) ---
    const contactForm = document.getElementById('contact-form');

    // Define globally if needed, or attach listener here
    window.submitContactForm = async (e) => {
        e.preventDefault();

        const btn = document.getElementById('form-submit-btn');
        const status = document.getElementById('form-status');
        const name = document.getElementById('form-name').value;
        const email = document.getElementById('form-email').value;
        const message = document.getElementById('form-message').value;
        const privacy = document.getElementById('form-privacy').checked;

        // UI Loading
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span>Invio in corso...</span>';
        btn.disabled = true;
        status.textContent = '';
        status.style.color = 'var(--text-sec)';

        try {
            const res = await fetch('api/send_email_form.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, email, message, privacy })
            });

            const json = await res.json();

            if (json.success) {
                status.textContent = '✅ Messaggio inviato! Ti risponderò via email.';
                status.style.color = '#2ecc71';
                contactForm.reset();
                setTimeout(() => { status.textContent = ''; }, 5000);
            } else {
                status.textContent = '❌ Errore: ' + (json.error || 'Riprova più tardi.');
                status.style.color = '#e74c3c';
            }
        } catch (err) {
            status.textContent = '❌ Errore di connessione.';
            status.style.color = '#e74c3c';
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    };



});
