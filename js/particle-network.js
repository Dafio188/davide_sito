/**
 * Particle Network Animation
 * Rotating particles with dynamic connections
 */

(function () {
    'use strict';

    const container = document.querySelector('.particle-network-container');
    if (!container) return;

    const canvas = document.createElement('canvas');
    canvas.className = 'particle-canvas';
    container.appendChild(canvas);
    const ctx = canvas.getContext('2d');

    let particles = [];
    let animationId;
    let mouseX = 0;
    let mouseY = 0;
    let centerX, centerY;

    const CONFIG = {
        particleCount: 60,
        minRadius: 80,
        maxRadius: 200,
        connectionDistance: 150,
        particleSize: 4,
        rotationSpeed: 0.005,
        colors: ['#3b82f6', '#8b5cf6', '#06b6d4', '#60a5fa', '#a78bfa']
    };

    function init() {
        resize();
        createParticles();
        animate();

        window.addEventListener('resize', resize);
        container.addEventListener('mousemove', onMouseMove);
    }

    function resize() {
        canvas.width = container.offsetWidth;
        canvas.height = container.offsetHeight;
        centerX = canvas.width / 2;
        centerY = canvas.height / 2;
    }

    function createParticles() {
        particles = [];
        for (let i = 0; i < CONFIG.particleCount; i++) {
            const angle = (Math.PI * 2 / CONFIG.particleCount) * i + Math.random() * 0.5;
            const radius = CONFIG.minRadius + Math.random() * (CONFIG.maxRadius - CONFIG.minRadius);

            particles.push({
                angle: angle,
                radius: radius,
                baseRadius: radius,
                x: 0,
                y: 0,
                size: CONFIG.particleSize + Math.random() * 2,
                speed: CONFIG.rotationSpeed * (0.5 + Math.random()),
                color: CONFIG.colors[Math.floor(Math.random() * CONFIG.colors.length)],
                pulseOffset: Math.random() * Math.PI * 2
            });
        }
    }

    function onMouseMove(e) {
        const rect = container.getBoundingClientRect();
        mouseX = e.clientX - rect.left;
        mouseY = e.clientY - rect.top;
    }

    function drawConnections() {
        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const distance = Math.sqrt(dx * dx + dy * dy);

                if (distance < CONFIG.connectionDistance) {
                    const opacity = 1 - (distance / CONFIG.connectionDistance);
                    ctx.beginPath();
                    ctx.strokeStyle = `rgba(59, 130, 246, ${opacity * 0.4})`;
                    ctx.lineWidth = 1;
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.stroke();
                }
            }
        }

        // Draw connections to mouse
        particles.forEach(p => {
            const dx = p.x - mouseX;
            const dy = p.y - mouseY;
            const distance = Math.sqrt(dx * dx + dy * dy);

            if (distance < 120) {
                const opacity = 1 - (distance / 120);
                ctx.beginPath();
                ctx.strokeStyle = `rgba(139, 92, 246, ${opacity * 0.6})`;
                ctx.lineWidth = 1.5;
                ctx.moveTo(p.x, p.y);
                ctx.lineTo(mouseX, mouseY);
                ctx.stroke();
            }
        });
    }

    function drawParticles() {
        const time = Date.now() * 0.001;

        particles.forEach(p => {
            // Update position with rotation
            p.angle += p.speed;
            p.x = centerX + Math.cos(p.angle) * p.radius;
            p.y = centerY + Math.sin(p.angle) * p.radius;

            // Pulsing effect
            const pulse = Math.sin(time * 2 + p.pulseOffset) * 0.3 + 1;
            const size = p.size * pulse;

            // Draw glow
            const gradient = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, size * 3);
            gradient.addColorStop(0, p.color);
            gradient.addColorStop(1, 'transparent');
            ctx.fillStyle = gradient;
            ctx.beginPath();
            ctx.arc(p.x, p.y, size * 3, 0, Math.PI * 2);
            ctx.fill();

            // Draw particle
            ctx.fillStyle = p.color;
            ctx.beginPath();
            ctx.arc(p.x, p.y, size, 0, Math.PI * 2);
            ctx.fill();
        });
    }

    function drawCenterCore() {
        const time = Date.now() * 0.001;
        const pulse = Math.sin(time) * 5 + 25;

        // Outer glow
        const gradient = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, pulse * 2);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
        gradient.addColorStop(0.5, 'rgba(139, 92, 246, 0.1)');
        gradient.addColorStop(1, 'transparent');
        ctx.fillStyle = gradient;
        ctx.beginPath();
        ctx.arc(centerX, centerY, pulse * 2, 0, Math.PI * 2);
        ctx.fill();

        // Inner core
        const coreGradient = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, 20);
        coreGradient.addColorStop(0, '#60a5fa');
        coreGradient.addColorStop(1, '#3b82f6');
        ctx.fillStyle = coreGradient;
        ctx.beginPath();
        ctx.arc(centerX, centerY, 15, 0, Math.PI * 2);
        ctx.fill();
    }

    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        drawCenterCore();
        drawConnections();
        drawParticles();

        animationId = requestAnimationFrame(animate);
    }

    // Initialize when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
