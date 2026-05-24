/**
 * Glowing Neon Loop Effect using Three.js
 * Implements an "Infinite Flow" loop similar to the reference styling.
 */

class GlowingLoop {
    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) return;

        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.mesh = null;
        this.material = null;

        this.mouse = new THREE.Vector2(0, 0);
        this.targetMouse = new THREE.Vector2(0, 0);
        this.windowHalfX = window.innerWidth / 2;
        this.windowHalfY = window.innerHeight / 2;

        this.init();
        this.animate();
    }

    init() {
        const container = this.canvas.parentElement;
        const width = container.offsetWidth;
        const height = container.offsetHeight;

        // Scene setup
        this.scene = new THREE.Scene();

        // Camera positioned to frame the loop nicely
        this.camera = new THREE.PerspectiveCamera(40, width / height, 0.1, 100);
        this.camera.position.z = 18; // Pull back to see full loop

        this.renderer = new THREE.WebGLRenderer({
            canvas: this.canvas,
            alpha: true,
            antialias: true
        });
        this.renderer.setSize(width, height);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

        // --- Create the Neon Texture ---
        const canvas = document.createElement('canvas');
        canvas.width = 128; // Low res is fine for gradients
        canvas.height = 32;
        const context = canvas.getContext('2d');

        // Create a gradient similar to the reference (Blue/Purple/Cyan)
        const gradient = context.createLinearGradient(0, 0, 128, 0);
        gradient.addColorStop(0, '#000000'); // Gap
        gradient.addColorStop(0.1, '#3b82f6'); // Blue
        gradient.addColorStop(0.4, '#d946ef'); // Purple
        gradient.addColorStop(0.7, '#8b5cf6'); // Violet
        gradient.addColorStop(0.9, '#3b82f6'); // Blue Loop
        gradient.addColorStop(1, '#000000'); // Gap

        context.fillStyle = gradient;
        context.fillRect(0, 0, 128, 32);

        // Load as texture
        const texture = new THREE.CanvasTexture(canvas);
        texture.wrapS = THREE.RepeatWrapping;
        texture.wrapT = THREE.RepeatWrapping;

        // --- Geometry: Torus Knot (Infinite Loop look) ---
        // Radius: 3.5, Tube: 1.2, TubularSegments: 200, RadialSegments: 20, p: 2, q: 3
        // Change p/q to alter loop complexity. 2,3 is a standard trefoil knot.
        const geometry = new THREE.TorusKnotGeometry(3.5, 0.8, 256, 32, 2, 3);

        // --- Material: Using the texture effectively ---
        this.material = new THREE.MeshBasicMaterial({
            map: texture,
            transparent: true,
            opacity: 0.9,
            side: THREE.DoubleSide
        });

        this.mesh = new THREE.Mesh(geometry, this.material);
        this.scene.add(this.mesh);

        // Events
        document.addEventListener('mousemove', this.onMouseMove.bind(this), false);
        window.addEventListener('resize', this.onWindowResize.bind(this), false);

        // Slightly rotate initially
        this.mesh.rotation.x = 0.5;
        this.mesh.rotation.y = 0.2;
    }

    onMouseMove(event) {
        this.targetMouse.x = (event.clientX - this.windowHalfX) * 0.005; // Less sensitivity
        this.targetMouse.y = (event.clientY - this.windowHalfY) * 0.005;
    }

    onWindowResize() {
        const container = this.canvas.parentElement;
        if (!container) return;

        this.windowHalfX = window.innerWidth / 2;
        this.windowHalfY = window.innerHeight / 2;

        this.camera.aspect = container.offsetWidth / container.offsetHeight;
        this.camera.updateProjectionMatrix();

        this.renderer.setSize(container.offsetWidth, container.offsetHeight);
    }

    animate() {
        requestAnimationFrame(this.animate.bind(this));

        // 1. Texture Offset Animation (The Flow Effect)
        // This is the magic key - moving the texture makes it look like light flowing through tubes
        if (this.material.map) {
            this.material.map.offset.x -= 0.008; // Adjust speed of flow
        }

        // 2. Gentle floating Rotation
        // Add subtle rotation to the whole knot
        this.mesh.rotation.x += 0.002;
        this.mesh.rotation.y += 0.003;

        // 3. Mouse Interaction (Parallax)
        // Slowly interpolate camera position towards mouse target
        this.camera.position.x += (this.targetMouse.x - this.camera.position.x) * 0.05;
        this.camera.position.y += (-this.targetMouse.y - this.camera.position.y) * 0.05;
        this.camera.lookAt(this.scene.position);

        this.renderer.render(this.scene, this.camera);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('tubes-canvas')) {
        new GlowingLoop('tubes-canvas');
    }
});
