const STORAGE_KEY = "studywaysData";
const SESSION_KEY = "studywaysSession";

const initialData = {
  users: [
    { id: 1, role: "admin", name: "Admin Principal", email: "admin@studyways.com" },
    { id: 2, role: "teacher", name: "M. Karim", email: "karim@studyways.com" },
    { id: 3, role: "student", name: "Amina", email: "amina@studyways.com", plan: "premium" }
  ],
  courses: [
    { id: 1, title: "Mathematiques avancees", owner: "M. Karim", format: "Video" },
    { id: 2, title: "Initiation Excel", owner: "Mme Sonia", format: "Document" }
  ],
  visits: [12, 25, 19, 30, 28, 41, 36],
  requests: [
    { id: 1, student: "Amina", teacher: "M. Karim", course: "Mathematiques avancees", status: "en attente" }
  ],
  ratings: [
    { id: 1, course: "Mathematiques avancees", teacher: "M. Karim", score: 4.6 }
  ]
};

function getData() {
  const raw = localStorage.getItem(STORAGE_KEY);
  if (!raw) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(initialData));
    return initialData;
  }
  return JSON.parse(raw);
}

function saveData(data) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
}

function setSession(user) {
  localStorage.setItem(SESSION_KEY, JSON.stringify(user));
}

function getSession() {
  const raw = localStorage.getItem(SESSION_KEY);
  return raw ? JSON.parse(raw) : null;
}

function clearSession() {
  localStorage.removeItem(SESSION_KEY);
}

function requireRole(role) {
  const user = getSession();
  if (!user || user.role !== role) {
    window.location.href = "login.html";
    return null;
  }
  return user;
}

function renderVisitsChart(canvasId, visits) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  const ctx = canvas.getContext("2d");
  const width = canvas.width;
  const height = canvas.height;
  ctx.clearRect(0, 0, width, height);

  const max = Math.max(...visits, 1);
  const gap = width / (visits.length + 1);

  ctx.strokeStyle = "#7a1028";
  ctx.lineWidth = 2;
  ctx.beginPath();

  visits.forEach((value, index) => {
    const x = gap * (index + 1);
    const y = height - (value / max) * (height - 20) - 10;
    if (index === 0) ctx.moveTo(x, y);
    else ctx.lineTo(x, y);
  });
  ctx.stroke();

  ctx.fillStyle = "#7a1028";
  visits.forEach((value, index) => {
    const x = gap * (index + 1);
    const y = height - (value / max) * (height - 20) - 10;
    ctx.beginPath();
    ctx.arc(x, y, 3, 0, Math.PI * 2);
    ctx.fill();
  });
}

function logout() {
  clearSession();
  window.location.href = "../index.html";
}

window.studyways = {
  getData,
  saveData,
  setSession,
  getSession,
  clearSession,
  requireRole,
  renderVisitsChart,
  logout
};

/**
 * DOM Content Loaded - Initialize all interactive features
 */
document.addEventListener('DOMContentLoaded', function() {
  initPageLoader();
  initScrollAnimations();
  initCounterAnimation();
  initSmoothScroll();
  initNavbarEffects();
  initInteractiveElements();
});

/**
 * Page Loader Animation
 */
function initPageLoader() {
  const pageLoader = document.getElementById('pageLoader');
  if (pageLoader) {
    window.addEventListener('load', function() {
      setTimeout(() => {
        pageLoader.classList.add('hide');
      }, 300);
    });
  }
}

/**
 * Scroll Animations - Reveal elements on scroll
 */
function initScrollAnimations() {
  const revealElements = document.querySelectorAll('.reveal');
  
  if (!revealElements.length) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });

  revealElements.forEach(el => observer.observe(el));
}

/**
 * Counter Animation - Count up numbers
 */
function initCounterAnimation() {
  const counters = document.querySelectorAll('[data-counter]');
  
  if (!counters.length) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const element = entry.target;
        const targetValue = parseInt(element.getAttribute('data-counter'));
        animateCounter(element, targetValue);
        observer.unobserve(element);
      }
    });
  }, { threshold: 0.5 });

  counters.forEach(counter => observer.observe(counter));
}

/**
 * Animate counter from 0 to target
 */
function animateCounter(element, target) {
  let current = 0;
  const increment = Math.ceil(target / 30); // 30 steps animation
  
  const timer = setInterval(() => {
    current += increment;
    if (current >= target) {
      current = target;
      clearInterval(timer);
    }
    element.textContent = current;
  }, 20);
}

/**
 * Smooth Scroll to Sections
 */
function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      const href = this.getAttribute('href');
      if (href === '#') return;
      
      e.preventDefault();
      const target = document.querySelector(href);
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });
}

/**
 * Navbar Effects - Add shadow on scroll
 */
function initNavbarEffects() {
  const navbar = document.querySelector('.navbar');
  if (!navbar) return;

  window.addEventListener('scroll', function() {
    if (window.scrollY > 10) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  }, { passive: true });
}

/**
 * Interactive Elements
 */
function initInteractiveElements() {
  // Mobile menu toggle
  const mobileMenuBtn = document.getElementById('mobileMenuBtn');
  const mainNav = document.getElementById('mainNav');

  if (mobileMenuBtn && mainNav) {
    mobileMenuBtn.addEventListener('click', function() {
      this.classList.toggle('active');
      mainNav.style.display = mainNav.style.display === 'flex' ? 'none' : 'flex';
    });
  }

  // Language menu toggle
  const langMenuBtn = document.getElementById('langMenuBtn');
  const langMenuPanel = document.getElementById('langMenuPanel');

  if (langMenuBtn && langMenuPanel) {
    langMenuBtn.addEventListener('click', function() {
      const isOpen = langMenuPanel.hasAttribute('hidden');
      if (isOpen) {
        langMenuPanel.removeAttribute('hidden');
        this.setAttribute('aria-expanded', 'true');
      } else {
        langMenuPanel.setAttribute('hidden', '');
        this.setAttribute('aria-expanded', 'false');
      }
    });

    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
      if (!langMenuBtn.contains(e.target) && !langMenuPanel.contains(e.target)) {
        langMenuPanel.setAttribute('hidden', '');
        langMenuBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // Fab button - Scroll to top
  const fabBtn = document.querySelector('.fab-top');
  if (fabBtn) {
    window.addEventListener('scroll', function() {
      if (window.scrollY > 300) {
        fabBtn.classList.add('is-visible');
      } else {
        fabBtn.classList.remove('is-visible');
      }
    }, { passive: true });

    fabBtn.addEventListener('click', function() {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  }

  // Add hover effects to interactive cards
  const interactiveCards = document.querySelectorAll('.icon-card--interactive');
  interactiveCards.forEach(card => {
    card.addEventListener('click', function() {
      interactiveCards.forEach(c => c.classList.remove('icon-card--active'));
      this.classList.add('icon-card--active');
    });
  });
}

/**
 * Utility: Add animation class to elements
 */
function addAnimation(element, animationName, duration = 0.5) {
  element.style.animation = `${animationName} ${duration}s cubic-bezier(0.34, 1.56, 0.64, 1)`;
  setTimeout(() => {
    element.style.animation = '';
  }, duration * 1000);
}
