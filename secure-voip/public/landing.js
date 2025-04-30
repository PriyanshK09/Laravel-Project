document.addEventListener('DOMContentLoaded', function() {
    // Set current year in footer
    document.getElementById('current-year').textContent = new Date().getFullYear();

    // Mobile menu toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mainNav = document.querySelector('.main-nav');
    
    if (mobileMenuBtn && mainNav) {
        mobileMenuBtn.addEventListener('click', function() {
            mainNav.classList.toggle('active');
            this.classList.toggle('active');
        });
    }

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth'
                });
                
                // Close mobile menu if open
                if (mainNav && mainNav.classList.contains('active')) {
                    mainNav.classList.remove('active');
                    if (mobileMenuBtn) mobileMenuBtn.classList.remove('active');
                }
            }
        });
    });

    // Encryption diagrams
    initEncryptionDiagrams();
});

function initEncryptionDiagrams() {
    // Symmetric encryption diagram
    const symmetricCanvas = document.getElementById('symmetric-diagram');
    if (symmetricCanvas) {
        const symmetricCtx = symmetricCanvas.getContext('2d');
        initDiagram(symmetricCanvas, symmetricCtx, 'symmetric');
    }

    // Asymmetric encryption diagram
    const asymmetricCanvas = document.getElementById('asymmetric-diagram');
    if (asymmetricCanvas) {
        const asymmetricCtx = asymmetricCanvas.getContext('2d');
        initDiagram(asymmetricCanvas, asymmetricCtx, 'asymmetric');
    }
}

function initDiagram(canvas, ctx, type) {
    // Set canvas dimensions
    function resizeCanvas() {
        canvas.width = canvas.offsetWidth;
        canvas.height = canvas.offsetHeight;
    }
    
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    // Particles array
    let particles = [];
    let isAnimating = true;

    // Particle class
    class Particle {
        constructor(x, y, size, speedX, speedY, color) {
            this.x = x;
            this.y = y;
            this.size = size;
            this.speedX = speedX;
            this.speedY = speedY;
            this.color = color;
            this.alpha = 1;
        }

        draw() {
            ctx.globalAlpha = this.alpha;
            ctx.fillStyle = this.color;
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fill();
            ctx.globalAlpha = 1;
        }

        update() {
            this.x += this.speedX;
            this.y += this.speedY;
            this.alpha -= 0.01;
        }
    }

    // Create particles
    function createParticles(x, y, color) {
        for (let i = 0; i < 5; i++) {
            const size = Math.random() * 2 + 1;
            const speedX = (Math.random() - 0.5) * 2;
            const speedY = (Math.random() - 0.5) * 2;
            particles.push(new Particle(x, y, size, speedX, speedY, color));
        }
    }

    // Draw node
    function drawNode(x, y, color, label) {
        // Draw circle
        ctx.fillStyle = color;
        ctx.beginPath();
        ctx.arc(x, y, 30, 0, Math.PI * 2);
        ctx.fill();

        // Draw border
        ctx.strokeStyle = '#10B981';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.arc(x, y, 30, 0, Math.PI * 2);
        ctx.stroke();

        // Draw label
        ctx.fillStyle = '#FFFFFF';
        ctx.font = '12px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(label, x, y + 50);
    }

    // Draw arrow
    function drawArrow(fromX, fromY, toX, toY, color) {
        const headLength = 10;
        const angle = Math.atan2(toY - fromY, toX - fromX);

        // Draw line
        ctx.strokeStyle = color;
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(fromX, fromY);
        ctx.lineTo(toX, toY);
        ctx.stroke();

        // Draw arrowhead
        ctx.beginPath();
        ctx.moveTo(toX, toY);
        ctx.lineTo(toX - headLength * Math.cos(angle - Math.PI / 6), toY - headLength * Math.sin(angle - Math.PI / 6));
        ctx.lineTo(toX - headLength * Math.cos(angle + Math.PI / 6), toY - headLength * Math.sin(angle + Math.PI / 6));
        ctx.closePath();
        ctx.fillStyle = color;
        ctx.fill();
    }

    // Draw icon
    function drawIcon(x, y, icon, color) {
        ctx.fillStyle = color;
        ctx.font = '16px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(icon === 'lock' ? '🔒' : '🔓', x, y);
    }

    // Draw symmetric encryption diagram
    function drawSymmetricDiagram() {
        // Draw sender and receiver
        drawNode(100, canvas.height / 2, '#1E293B', 'Sender');
        drawNode(canvas.width - 100, canvas.height / 2, '#1E293B', 'Receiver');

        // Draw key
        const keyX = canvas.width / 2;
        const keyY = canvas.height / 2 - 60;
        drawNode(keyX, keyY, '#10B981', 'Shared Key');

        // Draw arrows for key
        drawArrow(keyX, keyY + 20, 100 + 40, canvas.height / 2 - 20, '#10B981');
        drawArrow(keyX, keyY + 20, canvas.width - 100 - 40, canvas.height / 2 - 20, '#10B981');

        // Draw message path
        const messageStartX = 100 + 40;
        const messageEndX = canvas.width - 100 - 40;
        const messageY = canvas.height / 2 + 20;

        // Draw lock and unlock icons
        drawIcon(messageStartX + 60, messageY - 15, 'lock', '#10B981');
        drawIcon(messageEndX - 60, messageY - 15, 'unlock', '#10B981');

        // Draw message arrow
        drawArrow(messageStartX, messageY, messageEndX, messageY, '#FFFFFF');

        // Create particles along the path
        if (Math.random() < 0.2) {
            const particleX = messageStartX + Math.random() * (messageEndX - messageStartX);
            createParticles(particleX, messageY, '#10B981');
        }
    }

    // Draw asymmetric encryption diagram
    function drawAsymmetricDiagram() {
        // Draw sender and receiver
        drawNode(100, canvas.height / 2, '#1E293B', 'Sender');
        drawNode(canvas.width - 100, canvas.height / 2, '#1E293B', 'Receiver');

        // Draw keys
        drawNode(100, canvas.height / 2 - 80, '#10B981', 'Private Key A');
        drawNode(100, canvas.height / 2 + 80, '#10B981', 'Public Key B');

        drawNode(canvas.width - 100, canvas.height / 2 - 80, '#10B981', 'Public Key A');
        drawNode(canvas.width - 100, canvas.height / 2 + 80, '#10B981', 'Private Key B');

        // Draw message paths
        const messageStartX = 100 + 40;
        const messageEndX = canvas.width - 100 - 40;
        const messageY = canvas.height / 2;

        // Draw lock and unlock icons
        drawIcon(messageStartX + 60, messageY - 15, 'lock', '#10B981');
        drawIcon(messageEndX - 60, messageY - 15, 'unlock', '#10B981');

        // Draw message arrow
        drawArrow(messageStartX, messageY, messageEndX, messageY, '#FFFFFF');

        // Create particles along the path
        if (Math.random() < 0.2) {
            const particleX = messageStartX + Math.random() * (messageEndX - messageStartX);
            createParticles(particleX, messageY, '#10B981');
        }
    }

    // Animation loop
    function animate() {
        if (!isAnimating) {
            requestAnimationFrame(animate);
            return;
        }

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Draw diagram based on encryption type
        if (type === 'symmetric') {
            drawSymmetricDiagram();
        } else {
            drawAsymmetricDiagram();
        }

        // Update and draw particles
        particles = particles.filter(p => p.alpha > 0);
        particles.forEach(p => {
            p.update();
            p.draw();
        });

        requestAnimationFrame(animate);
    }

    // Toggle animation on click
    canvas.addEventListener('click', function() {
        isAnimating = !isAnimating;
    });

    // Start animation
    animate();
}