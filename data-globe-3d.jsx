import React, { useRef, useEffect, useState } from 'react';
import * as THREE from 'three';
import { TrendingUp, Shield, Zap } from 'lucide-react';

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

      // Color variation (blue shades)
      const colorChoice = Math.random();
      if (colorChoice < 0.33) {
        colors[i * 3] = 0.23; colors[i * 3 + 1] = 0.51; colors[i * 3 + 2] = 0.96; // #3b82f6
      } else if (colorChoice < 0.66) {
        colors[i * 3] = 0.54; colors[i * 3 + 1] = 0.36; colors[i * 3 + 2] = 0.96; // #8b5cf6
      } else {
        colors[i * 3] = 0.06; colors[i * 3 + 1] = 0.71; colors[i * 3 + 2] = 0.83; // #06b6d4
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

      // Smooth mouse follow
      targetX = mouseX * 0.3;
      targetY = mouseY * 0.3;
      globe.rotation.y += (targetX - globe.rotation.y) * 0.05;
      globe.rotation.x += (targetY - globe.rotation.x) * 0.05;

      // Auto rotation
      globe.rotation.y += 0.002;
      glowSphere.rotation.y -= 0.001;

      // Update particles
      const positions = particleSystem.geometry.attributes.position.array;
      particles.forEach((particle, i) => {
        particle.theta += particle.speed;
        
        positions[i * 3] = particle.radius * Math.sin(particle.phi) * Math.cos(particle.theta);
        positions[i * 3 + 1] = particle.radius * Math.sin(particle.phi) * Math.sin(particle.theta);
        positions[i * 3 + 2] = particle.radius * Math.cos(particle.phi);
      });
      particleSystem.geometry.attributes.position.needsUpdate = true;

      // Update connection lines
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

    // Resize handler
    const handleResize = () => {
      if (!containerRef.current) return;
      const width = containerRef.current.clientWidth;
      const height = containerRef.current.clientHeight;
      camera.aspect = width / height;
      camera.updateProjectionMatrix();
      renderer.setSize(width, height);
    };

    window.addEventListener('resize', handleResize);

    // Cleanup
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
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap');

        .globe-container {
          width: 100%;
          height: 700px;
          background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #0f1629 100%);
          border-radius: 32px;
          position: relative;
          overflow: hidden;
          font-family: 'Orbitron', sans-serif;
        }

        .globe-container::before {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: 
            radial-gradient(circle at 50% 50%, rgba(59, 130, 246, 0.1) 0%, transparent 50%);
          pointer-events: none;
          z-index: 1;
        }

        .globe-canvas-wrapper {
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          z-index: 2;
        }

        .globe-overlay {
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          z-index: 3;
          pointer-events: none;
        }

        .globe-title {
          position: absolute;
          top: 40px;
          left: 40px;
          opacity: 0;
          animation: fadeInTitle 1s ease-out 0.5s forwards;
        }

        .title-badge {
          display: inline-flex;
          align-items: center;
          gap: 8px;
          background: rgba(59, 130, 246, 0.1);
          border: 1px solid rgba(59, 130, 246, 0.3);
          padding: 8px 16px;
          border-radius: 100px;
          font-size: 11px;
          font-weight: 700;
          color: #60a5fa;
          letter-spacing: 1px;
          text-transform: uppercase;
          margin-bottom: 16px;
          box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
        }

        .main-title {
          font-size: 36px;
          font-weight: 900;
          color: #fff;
          margin-bottom: 8px;
          letter-spacing: -0.02em;
          text-shadow: 0 0 30px rgba(59, 130, 246, 0.5);
        }

        .main-title .highlight {
          background: linear-gradient(135deg, #3b82f6, #8b5cf6, #06b6d4);
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
          background-clip: text;
          animation: shimmer 3s ease-in-out infinite;
          background-size: 200% auto;
        }

        .sub-title {
          font-size: 14px;
          color: #94a3b8;
          font-weight: 400;
          font-family: 'JetBrains Mono', monospace;
        }

        .stats-panel {
          position: absolute;
          bottom: 40px;
          right: 40px;
          display: flex;
          gap: 20px;
          opacity: 0;
          animation: fadeInStats 1s ease-out 1s forwards;
        }

        .stat-card {
          background: rgba(15, 23, 42, 0.8);
          backdrop-filter: blur(10px);
          border: 1px solid rgba(59, 130, 246, 0.2);
          border-radius: 16px;
          padding: 20px 24px;
          min-width: 140px;
          position: relative;
          overflow: hidden;
        }

        .stat-card::before {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          height: 2px;
          background: linear-gradient(90deg, #3b82f6, #8b5cf6);
          opacity: 0.5;
        }

        .stat-icon {
          width: 32px;
          height: 32px;
          border-radius: 8px;
          display: flex;
          align-items: center;
          justify-content: center;
          margin-bottom: 12px;
          background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(139, 92, 246, 0.2));
        }

        .stat-value {
          font-size: 28px;
          font-weight: 900;
          color: #fff;
          font-family: 'JetBrains Mono', monospace;
          margin-bottom: 4px;
          text-shadow: 0 0 10px rgba(59, 130, 246, 0.5);
        }

        .stat-label {
          font-size: 11px;
          color: #64748b;
          font-weight: 600;
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }

        .center-pulse {
          position: absolute;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          width: 300px;
          height: 300px;
          border-radius: 50%;
          border: 1px solid rgba(59, 130, 246, 0.2);
          animation: pulse-ring 3s ease-out infinite;
          z-index: 1;
        }

        .scan-line {
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          height: 2px;
          background: linear-gradient(90deg, transparent, #3b82f6, transparent);
          animation: scan 4s linear infinite;
          z-index: 4;
          opacity: 0.3;
        }

        @keyframes fadeInTitle {
          from {
            opacity: 0;
            transform: translateY(-20px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }

        @keyframes fadeInStats {
          from {
            opacity: 0;
            transform: translateY(20px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }

        @keyframes pulse-ring {
          0% {
            transform: translate(-50%, -50%) scale(0.8);
            opacity: 0.8;
          }
          100% {
            transform: translate(-50%, -50%) scale(1.5);
            opacity: 0;
          }
        }

        @keyframes scan {
          0% {
            top: 0;
          }
          100% {
            top: 100%;
          }
        }

        @keyframes shimmer {
          0% {
            background-position: 0% 50%;
          }
          50% {
            background-position: 100% 50%;
          }
          100% {
            background-position: 0% 50%;
          }
        }

        @media (max-width: 768px) {
          .globe-container {
            height: 550px;
          }

          .globe-title {
            left: 20px;
            top: 20px;
          }

          .main-title {
            font-size: 24px;
          }

          .sub-title {
            font-size: 12px;
          }

          .stats-panel {
            bottom: 20px;
            right: 20px;
            left: 20px;
            flex-direction: column;
            gap: 12px;
          }

          .stat-card {
            min-width: auto;
            padding: 16px 20px;
          }

          .stat-value {
            font-size: 24px;
          }
        }
      `}</style>

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