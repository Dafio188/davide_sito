import React, { useState, useEffect, useRef } from 'react';
import { Shield, Code, Brain, Cloud, Lock, Cpu, Terminal, Database } from 'lucide-react';

const DavideSecurityNetwork = () => {
  const canvasRef = useRef(null);
  const [activeNode, setActiveNode] = useState(null);
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  const nodes = [
    { id: 1, x: 50, y: 35, icon: Shield, label: 'Security', color: '#3b82f6', size: 'large' },
    { id: 2, x: 25, y: 20, icon: Lock, label: 'Encryption', color: '#06b6d4', size: 'small' },
    { id: 3, x: 75, y: 20, icon: Terminal, label: 'DevOps', color: '#8b5cf6', size: 'small' },
    { id: 4, x: 20, y: 55, icon: Code, label: 'Development', color: '#10b981', size: 'medium' },
    { id: 5, x: 80, y: 55, icon: Brain, label: 'AI/ML', color: '#ec4899', size: 'medium' },
    { id: 6, x: 50, y: 70, icon: Cloud, label: 'Cloud', color: '#f59e0b', size: 'medium' },
    { id: 7, x: 30, y: 82, icon: Database, label: 'Database', color: '#14b8a6', size: 'small' },
    { id: 8, x: 70, y: 82, icon: Cpu, label: 'API', color: '#a855f7', size: 'small' },
  ];

  const connections = [
    { from: 1, to: 2 }, { from: 1, to: 3 }, { from: 1, to: 4 }, { from: 1, to: 5 },
    { from: 4, to: 2 }, { from: 5, to: 3 }, { from: 4, to: 6 }, { from: 5, to: 6 },
    { from: 6, to: 7 }, { from: 6, to: 8 }, { from: 4, to: 7 }, { from: 5, to: 8 },
  ];

  useEffect(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width * 2;
    canvas.height = rect.height * 2;
    ctx.scale(2, 2);

    let animationFrame;
    let flowOffset = 0;

    const drawConnections = () => {
      connections.forEach((conn) => {
        const fromNode = nodes.find(n => n.id === conn.from);
        const toNode = nodes.find(n => n.id === conn.to);

        if (!fromNode || !toNode) return;

        const fromX = (fromNode.x / 100) * rect.width;
        const fromY = (fromNode.y / 100) * rect.height;
        const toX = (toNode.x / 100) * rect.width;
        const toY = (toNode.y / 100) * rect.height;

        // Draw connection line
        ctx.beginPath();
        ctx.moveTo(fromX, fromY);
        ctx.lineTo(toX, toY);
        
        const isActive = activeNode === conn.from || activeNode === conn.to;
        ctx.strokeStyle = isActive ? 'rgba(59, 130, 246, 0.5)' : 'rgba(148, 163, 184, 0.15)';
        ctx.lineWidth = isActive ? 2.5 : 1.5;
        ctx.stroke();

        // Draw flowing data particles
        if (isActive) {
          const dx = toX - fromX;
          const dy = toY - fromY;
          const steps = 3;

          for (let i = 0; i < steps; i++) {
            const progress = ((flowOffset + i * 0.33) % 1);
            const x = fromX + dx * progress;
            const y = fromY + dy * progress;

            ctx.beginPath();
            ctx.arc(x, y, 3, 0, Math.PI * 2);
            
            const gradient = ctx.createRadialGradient(x, y, 0, x, y, 3);
            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.9)');
            gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');
            ctx.fillStyle = gradient;
            ctx.fill();
          }
        }
      });
    };

    const animate = () => {
      ctx.clearRect(0, 0, rect.width, rect.height);
      flowOffset = (flowOffset + 0.012) % 1;
      drawConnections();
      animationFrame = requestAnimationFrame(animate);
    };

    animate();

    return () => {
      if (animationFrame) {
        cancelAnimationFrame(animationFrame);
      }
    };
  }, [activeNode]);

  const NetworkNode = ({ node, index }) => {
    const sizes = {
      large: { core: 64, pulse: 90 },
      medium: { core: 52, pulse: 76 },
      small: { core: 44, pulse: 64 }
    };
    
    const size = sizes[node.size];

    return (
      <div
        className="network-node"
        style={{
          left: `${node.x}%`,
          top: `${node.y}%`,
          animationDelay: `${index * 0.1}s`
        }}
        onMouseEnter={() => setActiveNode(node.id)}
        onMouseLeave={() => setActiveNode(null)}
      >
        <div 
          className={`node-pulse ${activeNode === node.id ? 'active' : ''}`}
          style={{ 
            borderColor: node.color,
            width: `${size.pulse}px`,
            height: `${size.pulse}px`
          }}
        />
        <div 
          className="node-core"
          style={{ 
            width: `${size.core}px`,
            height: `${size.core}px`,
            background: `linear-gradient(135deg, ${node.color}f0, ${node.color}cc)`,
            boxShadow: activeNode === node.id 
              ? `0 0 25px ${node.color}70, 0 0 50px ${node.color}30`
              : `0 8px 20px ${node.color}30`
          }}
        >
          <node.icon size={node.size === 'large' ? 24 : node.size === 'medium' ? 20 : 16} color="white" strokeWidth={2.5} />
        </div>
        <div className="node-label" style={{ color: node.color }}>
          {node.label}
        </div>
      </div>
    );
  };

  return (
    <div className="security-network-container">
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap');

        .security-network-container {
          width: 100%;
          height: 700px;
          background: linear-gradient(160deg, #f0f9ff 0%, #e0f2fe 50%, #dbeafe 100%);
          border-radius: 32px;
          position: relative;
          overflow: hidden;
          font-family: 'Inter', sans-serif;
        }

        .security-network-container::before {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: 
            radial-gradient(circle at 25% 30%, rgba(59, 130, 246, 0.04) 0%, transparent 50%),
            radial-gradient(circle at 75% 70%, rgba(139, 92, 246, 0.04) 0%, transparent 50%),
            radial-gradient(circle at 50% 50%, rgba(16, 185, 129, 0.03) 0%, transparent 60%);
          pointer-events: none;
        }

        .network-canvas {
          width: 100%;
          height: 100%;
          position: absolute;
          top: 0;
          left: 0;
          z-index: 1;
        }

        .network-node {
          position: absolute;
          transform: translate(-50%, -50%);
          z-index: 2;
          cursor: pointer;
          animation: nodeAppear 1s cubic-bezier(0.34, 1.56, 0.64, 1) backwards;
          transition: transform 0.3s ease;
        }

        .network-node:hover {
          transform: translate(-50%, -50%) scale(1.05);
        }

        .node-pulse {
          position: absolute;
          border: 2px solid;
          border-radius: 50%;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          animation: pulse 3s ease-in-out infinite;
          opacity: 0.25;
        }

        .network-node:hover .node-pulse {
          animation: pulseFast 1.2s ease-in-out infinite;
        }

        .node-pulse.active {
          opacity: 0.6;
          animation: pulseFast 1.2s ease-in-out infinite;
        }

        .node-core {
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          position: relative;
          transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
          border: 3px solid rgba(255, 255, 255, 0.95);
          backdrop-filter: blur(10px);
        }

        .network-node:hover .node-core {
          transform: scale(1.1);
          border-color: rgba(255, 255, 255, 1);
        }

        .node-label {
          position: absolute;
          top: calc(100% + 12px);
          left: 50%;
          transform: translateX(-50%);
          font-size: 11px;
          font-weight: 700;
          white-space: nowrap;
          text-transform: uppercase;
          letter-spacing: 0.8px;
          opacity: 0;
          transition: opacity 0.3s ease, transform 0.3s ease;
          text-shadow: 0 2px 12px rgba(255, 255, 255, 0.95);
          pointer-events: none;
        }

        .network-node:hover .node-label {
          opacity: 1;
          transform: translateX(-50%) translateY(-4px);
        }

        .header-section {
          position: absolute;
          top: 40px;
          left: 40px;
          z-index: 3;
          max-width: 420px;
          opacity: 0;
          animation: fadeInUp 1s ease-out 0.3s forwards;
        }

        .header-badge {
          display: inline-flex;
          align-items: center;
          gap: 8px;
          background: rgba(255, 255, 255, 0.95);
          backdrop-filter: blur(12px);
          padding: 8px 16px;
          border-radius: 100px;
          font-size: 11px;
          font-weight: 700;
          color: #3b82f6;
          letter-spacing: 0.8px;
          text-transform: uppercase;
          margin-bottom: 16px;
          border: 1px solid rgba(59, 130, 246, 0.2);
          box-shadow: 0 4px 20px rgba(59, 130, 246, 0.15);
        }

        .header-title {
          font-size: 32px;
          font-weight: 800;
          color: #0f172a;
          line-height: 1.15;
          margin-bottom: 12px;
          letter-spacing: -0.03em;
        }

        .header-title .gradient-text {
          background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
          background-clip: text;
        }

        .header-subtitle {
          font-size: 15px;
          color: #64748b;
          line-height: 1.6;
          font-weight: 500;
        }

        .metrics-container {
          position: absolute;
          bottom: 40px;
          right: 40px;
          display: flex;
          flex-direction: column;
          gap: 12px;
          z-index: 3;
        }

        .metric-card {
          background: rgba(255, 255, 255, 0.95);
          backdrop-filter: blur(12px);
          padding: 16px 22px;
          border-radius: 16px;
          border-left: 4px solid;
          box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
          min-width: 200px;
          opacity: 0;
          animation: slideInRight 0.8s ease-out forwards;
          transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .metric-card:nth-child(1) { animation-delay: 0.5s; }
        .metric-card:nth-child(2) { animation-delay: 0.7s; }
        .metric-card:nth-child(3) { animation-delay: 0.9s; }

        .metric-card:hover {
          transform: translateX(-6px);
          box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
        }

        .metric-value {
          font-size: 26px;
          font-weight: 800;
          font-family: 'JetBrains Mono', monospace;
          line-height: 1;
          margin-bottom: 5px;
        }

        .metric-label {
          font-size: 11px;
          font-weight: 600;
          color: #64748b;
          text-transform: uppercase;
          letter-spacing: 0.6px;
        }

        .central-grid {
          position: absolute;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          width: 280px;
          height: 280px;
          z-index: 0;
          opacity: 0.5;
        }

        .grid-ring {
          position: absolute;
          border: 1px solid rgba(59, 130, 246, 0.08);
          border-radius: 50%;
          animation: rotateRing 30s linear infinite;
        }

        .grid-ring:nth-child(1) {
          width: 100%;
          height: 100%;
          border-top-color: rgba(59, 130, 246, 0.15);
        }

        .grid-ring:nth-child(2) {
          width: 75%;
          height: 75%;
          top: 12.5%;
          left: 12.5%;
          border-right-color: rgba(139, 92, 246, 0.15);
          animation-duration: 40s;
          animation-direction: reverse;
        }

        .grid-ring:nth-child(3) {
          width: 50%;
          height: 50%;
          top: 25%;
          left: 25%;
          border-bottom-color: rgba(16, 185, 129, 0.15);
          animation-duration: 50s;
        }

        @keyframes nodeAppear {
          from {
            opacity: 0;
            transform: translate(-50%, -50%) scale(0) rotate(180deg);
          }
          to {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1) rotate(0deg);
          }
        }

        @keyframes pulse {
          0%, 100% {
            transform: translate(-50%, -50%) scale(1);
            opacity: 0.25;
          }
          50% {
            transform: translate(-50%, -50%) scale(1.4);
            opacity: 0.1;
          }
        }

        @keyframes pulseFast {
          0%, 100% {
            transform: translate(-50%, -50%) scale(1);
            opacity: 0.5;
          }
          50% {
            transform: translate(-50%, -50%) scale(1.5);
            opacity: 0.15;
          }
        }

        @keyframes fadeInUp {
          from {
            opacity: 0;
            transform: translateY(20px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }

        @keyframes slideInRight {
          from {
            opacity: 0;
            transform: translateX(30px);
          }
          to {
            opacity: 1;
            transform: translateX(0);
          }
        }

        @keyframes rotateRing {
          from {
            transform: rotate(0deg);
          }
          to {
            transform: rotate(360deg);
          }
        }

        @media (max-width: 1024px) {
          .security-network-container {
            height: 600px;
          }

          .header-section {
            left: 30px;
            top: 30px;
            max-width: 350px;
          }

          .header-title {
            font-size: 26px;
          }

          .metrics-container {
            bottom: 30px;
            right: 30px;
          }
        }

        @media (max-width: 768px) {
          .security-network-container {
            height: 500px;
            border-radius: 24px;
          }

          .header-section {
            left: 20px;
            top: 20px;
            max-width: calc(100% - 40px);
          }

          .header-title {
            font-size: 22px;
          }

          .header-subtitle {
            font-size: 13px;
          }

          .metrics-container {
            bottom: 20px;
            right: 20px;
            gap: 8px;
          }

          .metric-card {
            min-width: 160px;
            padding: 12px 16px;
          }

          .metric-value {
            font-size: 20px;
          }

          .node-core {
            transform: scale(0.85);
          }
        }
      `}</style>

      <canvas 
        ref={canvasRef}
        className="network-canvas"
      />

      <div className="central-grid">
        <div className="grid-ring"></div>
        <div className="grid-ring"></div>
        <div className="grid-ring"></div>
      </div>

      {mounted && nodes.map((node, index) => (
        <NetworkNode key={node.id} node={node} index={index} />
      ))}

      <div className="header-section">
        <div className="header-badge">
          <Shield size={13} />
          <span>SECURE BY DESIGN</span>
        </div>
        <h2 className="header-title">
          Architettura <span className="gradient-text">Sicura e Scalabile</span>
        </h2>
        <p className="header-subtitle">
          Ogni componente lavora in sinergia per garantire sicurezza, performance e innovazione AI.
        </p>
      </div>

      <div className="metrics-container">
        <div className="metric-card" style={{ borderLeftColor: '#10b981' }}>
          <div className="metric-value" style={{ color: '#10b981' }}>Zero</div>
          <div className="metric-label">Vulnerabilità</div>
        </div>
        <div className="metric-card" style={{ borderLeftColor: '#3b82f6' }}>
          <div className="metric-value" style={{ color: '#3b82f6' }}>99.9%</div>
          <div className="metric-label">Uptime SLA</div>
        </div>
        <div className="metric-card" style={{ borderLeftColor: '#ec4899' }}>
          <div className="metric-value" style={{ color: '#ec4899' }}>24/7</div>
          <div className="metric-label">AI Monitoring</div>
        </div>
      </div>
    </div>
  );
};

export default DavideSecurityNetwork;