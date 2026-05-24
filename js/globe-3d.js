/**
 * Data Globe 3D Animation
 * Uses Three.js for 3D rendering
 */

(function () {
    'use strict';

    const container = document.getElementById('globe-canvas');
    if (!container) return;

    // Check if Three.js is loaded
    if (typeof THREE === 'undefined') {
        console.error('Three.js not loaded');
        return;
    }

    // Scene setup
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(45, container.offsetWidth / container.offsetHeight, 0.1, 1000);
    camera.position.z = 300;

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(container.offsetWidth, container.offsetHeight);
    renderer.setClearColor(0x000000, 0);
    container.appendChild(renderer.domElement);

    // Globe wireframe
    const globeGeometry = new THREE.SphereGeometry(60, 48, 48);
    const globeMaterial = new THREE.MeshBasicMaterial({
        color: 0x3b82f6,
        wireframe: true,
        transparent: true,
        opacity: 0.15
    });
    const globe = new THREE.Mesh(globeGeometry, globeMaterial);
    scene.add(globe);

    // Inner glow
    const glowGeometry = new THREE.SphereGeometry(58, 32, 32);
    const glowMaterial = new THREE.MeshBasicMaterial({
        color: 0x60a5fa,
        transparent: true,
        opacity: 0.1,
        side: THREE.BackSide
    });
    const glowSphere = new THREE.Mesh(glowGeometry, glowMaterial);
    scene.add(glowSphere);

    // Orbiting particles
    const particleCount = 150;
    const particles = [];
    const particleGeometry = new THREE.BufferGeometry();
    const positions = new Float32Array(particleCount * 3);
    const colors = new Float32Array(particleCount * 3);

    for (let i = 0; i < particleCount; i++) {
        const theta = Math.random() * Math.PI * 2;
        const phi = Math.random() * Math.PI;
        const radius = 80 + Math.random() * 60;

        positions[i * 3] = radius * Math.sin(phi) * Math.cos(theta);
        positions[i * 3 + 1] = radius * Math.sin(phi) * Math.sin(theta);
        positions[i * 3 + 2] = radius * Math.cos(phi);

        const colorChoice = Math.random();
        if (colorChoice < 0.33) {
            colors[i * 3] = 0.23; colors[i * 3 + 1] = 0.51; colors[i * 3 + 2] = 0.96;
        } else if (colorChoice < 0.66) {
            colors[i * 3] = 0.54; colors[i * 3 + 1] = 0.36; colors[i * 3 + 2] = 0.96;
        } else {
            colors[i * 3] = 0.06; colors[i * 3 + 1] = 0.71; colors[i * 3 + 2] = 0.83;
        }

        particles.push({ theta, phi, radius, speed: 0.002 + Math.random() * 0.003 });
    }

    particleGeometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    particleGeometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));

    const particleMaterial = new THREE.PointsMaterial({
        size: 3,
        vertexColors: true,
        transparent: true,
        opacity: 0.8,
        blending: THREE.AdditiveBlending
    });

    const particleSystem = new THREE.Points(particleGeometry, particleMaterial);
    scene.add(particleSystem);

    // Connection lines
    const lineGeometry = new THREE.BufferGeometry();
    const linePositions = new Float32Array(60 * 6);
    lineGeometry.setAttribute('position', new THREE.BufferAttribute(linePositions, 3));

    const lineMaterial = new THREE.LineBasicMaterial({
        color: 0x3b82f6,
        transparent: true,
        opacity: 0.3,
        blending: THREE.AdditiveBlending
    });

    const lines = new THREE.LineSegments(lineGeometry, lineMaterial);
    scene.add(lines);

    // Mouse interaction
    let mouseX = 0, mouseY = 0;

    container.parentElement.addEventListener('mousemove', (event) => {
        const rect = container.parentElement.getBoundingClientRect();
        mouseX = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        mouseY = -((event.clientY - rect.top) / rect.height) * 2 + 1;
    });

    // Stats animation
    let frame = 0;
    const animateStats = () => {
        if (frame < 100) {
            const nodesEl = document.getElementById('stat-nodes');
            const connEl = document.getElementById('stat-connections');
            const dataEl = document.getElementById('stat-data');

            if (nodesEl) nodesEl.textContent = Math.floor((frame / 100) * 847);
            if (connEl) connEl.textContent = Math.floor((frame / 100) * 2453);
            if (dataEl) dataEl.textContent = (Math.floor((frame / 100) * 18.4 * 10) / 10) + 'TB';
            frame++;
        }
    };

    // Animation loop
    function animate() {
        requestAnimationFrame(animate);

        // Smooth mouse follow
        globe.rotation.y += (mouseX * 0.3 - globe.rotation.y) * 0.05;
        globe.rotation.x += (mouseY * 0.3 - globe.rotation.x) * 0.05;

        // Auto rotation
        globe.rotation.y += 0.003;
        glowSphere.rotation.y -= 0.002;

        // Update particles
        const pos = particleSystem.geometry.attributes.position.array;
        particles.forEach((p, i) => {
            p.theta += p.speed;
            pos[i * 3] = p.radius * Math.sin(p.phi) * Math.cos(p.theta);
            pos[i * 3 + 1] = p.radius * Math.sin(p.phi) * Math.sin(p.theta);
            pos[i * 3 + 2] = p.radius * Math.cos(p.phi);
        });
        particleSystem.geometry.attributes.position.needsUpdate = true;

        // Update connection lines
        const linePos = lines.geometry.attributes.position.array;
        for (let i = 0; i < 30; i++) {
            const idx1 = Math.floor(Math.random() * particleCount) * 3;
            const idx2 = Math.floor(Math.random() * particleCount) * 3;

            linePos[i * 6] = pos[idx1];
            linePos[i * 6 + 1] = pos[idx1 + 1];
            linePos[i * 6 + 2] = pos[idx1 + 2];
            linePos[i * 6 + 3] = pos[idx2];
            linePos[i * 6 + 4] = pos[idx2 + 1];
            linePos[i * 6 + 5] = pos[idx2 + 2];
        }
        lines.geometry.attributes.position.needsUpdate = true;

        animateStats();
        renderer.render(scene, camera);
    }

    animate();

    // Resize handler
    window.addEventListener('resize', () => {
        camera.aspect = container.offsetWidth / container.offsetHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(container.offsetWidth, container.offsetHeight);
    });
})();
