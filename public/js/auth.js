// Auth Page Interactive Features
document.addEventListener('DOMContentLoaded', function() {

  // Password Toggle Functionality
  const togglePasswordBtn = document.getElementById('toggleRegisterPassword');
  const passwordInput = document.getElementById('password');

  if (togglePasswordBtn && passwordInput) {
    togglePasswordBtn.addEventListener('click', function() {
      const type = passwordInput.type === 'password' ? 'text' : 'password';
      passwordInput.type = type;
      togglePasswordBtn.textContent = type === 'password' ? 'Show' : 'Hide';

      // Add ripple effect
      createRipple(togglePasswordBtn, event);
    });
  }

  // Role Selection Handler
  const roleSelect = document.getElementById('role');
  const planWrap = document.getElementById('planWrap');
  const adminCodeWrap = document.getElementById('adminCodeWrap');
  const adminCodeInput = document.getElementById('adminCode');
  const planSelect = document.getElementById('plan');

  if (roleSelect) {
    roleSelect.addEventListener('change', function() {
      const role = this.value;

      // Animate transitions
      if (role === 'student') {
        slideDown(planWrap);
        slideUp(adminCodeWrap);
        if (planSelect) planSelect.required = false;
        if (adminCodeInput) adminCodeInput.required = false;
      } else if (role === 'admin') {
        slideUp(planWrap);
        slideDown(adminCodeWrap);
        if (planSelect) planSelect.required = false;
        if (adminCodeInput) adminCodeInput.required = true;
      } else {
        slideUp(planWrap);
        slideUp(adminCodeWrap);
        if (planSelect) planSelect.required = false;
        if (adminCodeInput) adminCodeInput.required = false;
      }
    });
  }

  // Password Strength Meter
  if (passwordInput) {
    const passwordMeterBar = document.getElementById('passwordMeterBar');
    const passwordHint = document.getElementById('passwordHint');

    passwordInput.addEventListener('input', function() {
      const password = this.value;
      const strength = calculatePasswordStrength(password);

      if (passwordMeterBar) {
        passwordMeterBar.style.width = strength.percentage + '%';
      }

      if (passwordHint) {
        passwordHint.textContent = strength.message;
        passwordHint.style.color = strength.color;
      }

      // Add visual feedback to input
      if (password.length > 0) {
        if (strength.percentage >= 80) {
          passwordInput.classList.remove('is-invalid');
          passwordInput.classList.add('is-valid');
        } else if (strength.percentage < 40) {
          passwordInput.classList.remove('is-valid');
          passwordInput.classList.add('is-invalid');
        } else {
          passwordInput.classList.remove('is-valid', 'is-invalid');
        }
      } else {
        passwordInput.classList.remove('is-valid', 'is-invalid');
      }
    });
  }

  // Form Validation with Animation
  const registerForm = document.getElementById('registerForm');
  if (registerForm) {
    registerForm.addEventListener('submit', function(e) {
      const inputs = this.querySelectorAll('input[required], select[required]');
      let isValid = true;

      inputs.forEach(input => {
        if (!input.value.trim()) {
          isValid = false;
          input.classList.add('is-invalid');
          shakeElement(input);
        } else {
          input.classList.remove('is-invalid');
        }
      });

      // Check password confirmation
      const passwordConfirm = document.getElementById('password_confirmation');
      if (passwordInput && passwordConfirm && passwordInput.value !== passwordConfirm.value) {
        isValid = false;
        passwordConfirm.classList.add('is-invalid');
        shakeElement(passwordConfirm);
        showError('Passwords do not match!');
        e.preventDefault();
      }

      if (!isValid) {
        e.preventDefault();
        shakeElement(registerForm);
      }
    });
  }

  // Input Focus Animations
  const inputs = document.querySelectorAll('.input-modern, select');
  inputs.forEach(input => {
    input.addEventListener('focus', function() {
      this.parentElement.classList.add('focused');
    });

    input.addEventListener('blur', function() {
      this.parentElement.classList.remove('focused');
      if (this.value.trim()) {
        this.classList.add('filled');
      } else {
        this.classList.remove('filled');
      }
    });
  });

  // Add ripple effect to buttons
  const buttons = document.querySelectorAll('.btn');
  buttons.forEach(button => {
    button.addEventListener('click', function(e) {
      createRipple(this, e);
    });
  });

  // Auto-hide error messages after 5 seconds
  const errorMsg = document.getElementById('error');
  if (errorMsg && errorMsg.textContent.trim()) {
    setTimeout(() => {
      fadeOut(errorMsg);
    }, 5000);
  }
});

// Helper Functions

function calculatePasswordStrength(password) {
  let strength = 0;
  let message = '';
  let color = '#dc2626';

  if (password.length === 0) {
    return { percentage: 0, message: 'Use 8+ characters with one uppercase letter and one number.', color: '#656565' };
  }

  // Length check
  if (password.length >= 8) strength += 25;
  if (password.length >= 12) strength += 15;

  // Uppercase check
  if (/[A-Z]/.test(password)) strength += 20;

  // Lowercase check
  if (/[a-z]/.test(password)) strength += 15;

  // Number check
  if (/[0-9]/.test(password)) strength += 15;

  // Special character check
  if (/[^A-Za-z0-9]/.test(password)) strength += 10;

  // Set message and color based on strength
  if (strength < 40) {
    message = 'Weak password. Add more characters and variety.';
    color = '#dc2626';
  } else if (strength < 70) {
    message = 'Fair password. Consider adding special characters.';
    color = '#f59e0b';
  } else if (strength < 90) {
    message = 'Good password! Your account will be secure.';
    color = '#10b981';
  } else {
    message = 'Excellent password! Maximum security.';
    color = '#059669';
  }

  return { percentage: strength, message, color };
}

function createRipple(element, event) {
  const ripple = document.createElement('span');
  ripple.classList.add('ripple');

  const rect = element.getBoundingClientRect();
  const size = Math.max(rect.width, rect.height);
  const x = event.clientX - rect.left - size / 2;
  const y = event.clientY - rect.top - size / 2;

  ripple.style.width = ripple.style.height = size + 'px';
  ripple.style.left = x + 'px';
  ripple.style.top = y + 'px';

  element.appendChild(ripple);

  // More smooth ripple animation
  requestAnimationFrame(() => {
    ripple.style.animation = 'rippleGo 0.65s cubic-bezier(0.4, 0, 0.2, 1) forwards';
  });

  setTimeout(() => {
    ripple.remove();
  }, 650);
}

function shakeElement(element) {
  element.classList.add('shake');
  setTimeout(() => {
    element.classList.remove('shake');
  }, 400);
}

function slideDown(element) {
  if (!element) return;
  element.style.display = 'block';
  element.style.maxHeight = '0';
  element.style.overflow = 'hidden';
  element.style.transition = 'max-height 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.4s ease';
  element.style.opacity = '0';

  // Trigger reflow
  void element.offsetHeight;

  setTimeout(() => {
    element.style.maxHeight = '500px';
    element.style.opacity = '1';
  }, 10);
}

function slideUp(element) {
  if (!element) return;
  element.style.maxHeight = '0';
  element.style.opacity = '0';
  element.style.transition = 'max-height 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.4s ease';

  setTimeout(() => {
    element.style.display = 'none';
  }, 400);
}

function fadeOut(element) {
  if (!element) return;
  element.style.transition = 'opacity 0.5s ease';
  element.style.opacity = '0';

  setTimeout(() => {
    element.style.display = 'none';
  }, 500);
}

function showError(message) {
  const errorElement = document.getElementById('error');
  if (errorElement) {
    errorElement.textContent = message;
    errorElement.style.display = 'block';
    errorElement.style.opacity = '1';
    shakeElement(errorElement);
  }
}

function showSuccess(message) {
  const successElement = document.getElementById('ok');
  if (successElement) {
    successElement.textContent = message;
    successElement.style.display = 'block';
    successElement.style.opacity = '1';
  }
}

// Parallax effect for auth panels with smooth transitions
let parallaxTimeout;
window.addEventListener('mousemove', function(e) {
  clearTimeout(parallaxTimeout);
  
  const authLeft = document.querySelector('.auth-left');
  if (authLeft) {
    const x = (e.clientX / window.innerWidth - 0.5) * 15;
    const y = (e.clientY / window.innerHeight - 0.5) * 15;
    authLeft.style.transform = `translate(${x}px, ${y}px)`;
    authLeft.style.transition = 'transform 0.1s ease-out';
  }
  
  parallaxTimeout = setTimeout(() => {
    if (authLeft) {
      authLeft.style.transition = 'transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1)';
      authLeft.style.transform = 'translate(0, 0)';
    }
  }, 100);
});
