import React, { useRef, useEffect, useState } from 'react';
import * as THREE from 'three';
import { TrendingUp, Shield, Zap } from 'lucide-react';
import './DataGlobe3D.css';

const DataGlobe3D = () => {
    const containerRef = useRef(null);
    const [stats, setStats] = useState({ nodes: 0, connections: 0, data: 0 });
    const [isLoaded, setIsLoaded] = useState(false);

    useEffect(() => {
        if (!containerRef.current) return;

        // Scene setup
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(
            45,
            containerRef.current.clientWidth / containerRef.current.clientHeight,
            0.1,
            1000
        );
        camera.position.z = 400;

        const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        renderer.setSize(containerRef.current.clientWidth, containerRef.current.clientHeight);
        renderer.setClearColor(0x000000, 0);
        containerRef.current.appendChild(renderer.domElement);

        // Globe
        const globeGeometry = new THREE.SphereGeometry(80, 64, 64);
        const globeMaterial = new THREE.MeshBasicMaterial({
            color: 0x3b82f6,
            wireframe: true,
            transparent: true,
            opacity: 0.15
        });
        const globe = new THREE.Mesh(globeGeometry, globeMaterial);
        scene.add(globe);

        // Inner glow sphere
        const glowGeometry = new THREE.SphereGeometry(78, 32, 32);
        const glowMaterial = new THREE.MeshBasicMaterial({
            color: 0x60a5fa,
            transparent: true,
            opacity: 0.1,
            side: THREE.BackSide
        });
        const glowSphere = new THREE.Mesh(glowGeometry, glowMaterial);
        scene.add(glowSphere);

        // Orbiting particles
        const particleCount = 200;
        const particles = [];
        const particleGeometry = new THREE.BufferGeometry();
        const positions = new Float32Array(particleCount * 3);
        const colors = new Float32Array(particleCount * 3);

        for (let i = 0; i < particleCount; i++) {
            const theta = Math.random() * Math.PI * 2;
            const phi = Math.random() * Math.PI;
            const radius = 100 + Math.random() * 80;

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

            particles.push({
                theta,
                phi,
                radius,
                speed: 0.001 + Math.random() * 0.002
            });
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
        const linePositions = new Float32Array(100 * 3);
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
        let mouseX = 0;
        let mouseY = 0;
        let targetX = 0;
        let targetY = 0;

        const onMouseMove = (event) => {
            const rect = containerRef.current.getBoundingClientRect();
            mouseX = ((event.clientX - rect.left) / rect.width) * 2 - 1;
            mouseY = -((event.clientY - rect.top) / rect.height) * 2 + 1;
        };

        window.addEventListener('mousemove', onMouseMove);

        // Stats animation
        let frame = 0;
        const animateStats = () => {
            if (frame < 100) {
                setStats({
                    nodes: Math.floor((frame / 100) * 847),
                    connections: Math.floor((frame / 100) * 2453),
                    data: Math.floor((frame / 100) * 18.4 * 10) / 10
                });
                frame++;
            }
        };

        // Animation loop
        let animationId;
        const animate = () => {
            animationId = requestAnimationFrame(animate);

            targetX = mouseX * 0.3;
            targetY = mouseY * 0.3;
            globe.rotation.y += (targetX - globe.rotation.y) * 0.05;
            globe.rotation.x += (targetY - globe.rotation.x) * 0.05;

            globe.rotation.y += 0.002;
            glowSphere.rotation.y -= 0.001;

            const positions = particleSystem.geometry.attributes.position.array;
            particles.forEach((particle, i) => {
                particle.theta += particle.speed;
                positions[i * 3] = particle.radius * Math.sin(particle.phi) * Math.cos(particle.theta);
                positions[i * 3 + 1] = particle.radius * Math.sin(particle.phi) * Math.sin(particle.theta);
                positions[i * 3 + 2] = particle.radius * Math.cos(particle.phi);
            });
            particleSystem.geometry.attributes.position.needsUpdate = true;

            const linePositions = lines.geometry.attributes.position.array;
            for (let i = 0; i < 50; i++) {
                const idx1 = Math.floor(Math.random() * particleCount) * 3;
                const idx2 = Math.floor(Math.random() * particleCount) * 3;

                linePositions[i * 6] = positions[idx1];
                linePositions[i * 6 + 1] = positions[idx1 + 1];
                linePositions[i * 6 + 2] = positions[idx1 + 2];
                linePositions[i * 6 + 3] = positions[idx2];
                linePositions[i * 6 + 4] = positions[idx2 + 1];
                linePositions[i * 6 + 5] = positions[idx2 + 2];
            }
            lines.geometry.attributes.position.needsUpdate = true;

            animateStats();
            renderer.render(scene, camera);
        };

        animate();
        setTimeout(() => setIsLoaded(true), 500);

        const handleResize = () => {
            if (!containerRef.current) return;
            const width = containerRef.current.clientWidth;
            const height = containerRef.current.clientHeight;
            camera.aspect = width / height;
            camera.updateProjectionMatrix();
            renderer.setSize(width, height);
        };

        window.addEventListener('resize', handleResize);

        return () => {
            window.removeEventListener('mousemove', onMouseMove);
            window.removeEventListener('resize', handleResize);
            cancelAnimationFrame(animationId);
            if (containerRef.current && renderer.domElement) {
                containerRef.current.removeChild(renderer.domElement);
            }
            renderer.dispose();
        };
    }, []);

    return (
        <div className="globe-container">
            <div className="center-pulse"></div>
            <div className="center-pulse" style={{ animationDelay: '1s' }}></div>
            <div className="center-pulse" style={{ animationDelay: '2s' }}></div>

            <div className="scan-line"></div>

            <div ref={containerRef} className="globe-canvas-wrapper" />

            <div className="globe-overlay">
                <div className="globe-title">
                    <div className="title-badge">
                        <Zap size={12} />
                        GLOBAL NETWORK
                    </div>
                    <h2 className="main-title">
                        <span className="highlight">Connected</span> Intelligence
                    </h2>
                    <p className="sub-title">Real-time data visualization • AI-powered security</p>
                </div>

                <div className="stats-panel">
                    <div className="stat-card">
                        <div className="stat-icon">
                            <Shield size={16} color="#3b82f6" />
                        </div>
                        <div className="stat-value">{stats.nodes}</div>
                        <div className="stat-label">Active Nodes</div>
                    </div>

                    <div className="stat-card">
                        <div className="stat-icon">
                            <TrendingUp size={16} color="#8b5cf6" />
                        </div>
                        <div className="stat-value">{stats.connections}</div>
                        <div className="stat-label">Connections</div>
                    </div>

                    <div className="stat-card">
                        <div className="stat-icon">
                            <Zap size={16} color="#06b6d4" />
                        </div>
                        <div className="stat-value">{stats.data}TB</div>
                        <div className="stat-label">Data Flow</div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default DataGlobe3D;
