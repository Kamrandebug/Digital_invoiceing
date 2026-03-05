/**
 * New Era Motion System 2026
 * Powered by GSAP & Intersection Observer
 */

document.addEventListener('DOMContentLoaded', () => {
    // Check if GSAP is available
    if (typeof gsap !== 'undefined') {
        initGsapAnimations();
    } else {
        // Fallback to Intersection Observer for basic reveals
        initBasicReveals();
    }

    initInteractiveCounters();
    initTypingEffect();
});

function initTypingEffect() {
    const textElement = document.querySelector('.typing-text');
    if (!textElement) return;

    const textToType = textElement.getAttribute('data-type-text') || "The future of billing is here.";
    let i = 0;

    function type() {
        if (i < textToType.length) {
            textElement.textContent += textToType.charAt(i);
            i++;
            setTimeout(type, 50 + Math.random() * 50);
        }
    }

    // Start typing when hero content is revealed or on load
    setTimeout(type, 1000);
}

function initGsapAnimations() {
    gsap.registerPlugin(ScrollTrigger);

    // Hero Section Entrance
    const heroTl = gsap.timeline();
    heroTl.from(".hero-title", {
        y: 50,
        opacity: 0,
        duration: 1,
        ease: "power4.out"
    })
        .from(".hero-subtitle", {
            y: 30,
            opacity: 0,
            duration: 0.8,
            ease: "power3.out"
        }, "-=0.6")
        .from(".hero-cta-group", {
            y: 20,
            opacity: 0,
            duration: 0.6,
            ease: "power2.out"
        }, "-=0.4")
        .from(".hero-mockup", {
            x: 50,
            opacity: 0,
            duration: 1.2,
            ease: "power3.out",
            clearProps: "opacity" // Clear opacity after animation to prevent it from vanishing if something else triggers
        }, "-=1");

    // Scroll Reveal for sections
    gsap.utils.toArray(".reveal-on-scroll").forEach(elem => {
        gsap.from(elem, {
            scrollTrigger: {
                trigger: elem,
                start: "top 85%",
                toggleActions: "play none none none"
            },
            y: 40,
            opacity: 0,
            duration: 1,
            ease: "power3.out"
        });
    });
}

function initBasicReveals() {
    const observerOptions = {
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
}

function initInteractiveCounters() {
    const counters = document.querySelectorAll('.stat-counter');
    const speed = 200;

    const startCounter = (counter) => {
        const target = +counter.getAttribute('data-target');
        const count = +counter.innerText;
        const inc = target / speed;

        if (count < target) {
            counter.innerText = Math.ceil(count + inc);
            setTimeout(() => startCounter(counter), 1);
        } else {
            counter.innerText = target;
        }
    };

    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                startCounter(entry.target);
            }
        });
    }, { threshold: 1.0 });

    counters.forEach(counter => counterObserver.observe(counter));
}
