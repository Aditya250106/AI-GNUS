
// auth.js - Updated version
if (window.location.pathname.includes("login") || window.location.pathname.includes("signup")) {
  if (localStorage.getItem("loggedInUser")) {
    window.location.href = "AIGNUS.htm";
  }
}

function togglePassword(fieldId = "password") {
  const field = document.getElementById(fieldId);
  const icon = field.parentNode.querySelector(".toggle-icon");

  if (field.type === "password") {
    field.type = "text";
    icon.classList.replace("fa-eye", "fa-eye-slash");
  } else {
    field.type = "password";
    icon.classList.replace("fa-eye-slash", "fa-eye");
  }
}

document.addEventListener("DOMContentLoaded", function () {
  // ✅ Login Logic
  const loginForm = document.getElementById("loginForm");
  if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const email = document.getElementById("loginEmail").value.trim();
      const password = document.getElementById("loginPassword").value;
      const message = document.getElementById("loginMessage");

      fetch('auth.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=login&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          localStorage.setItem("loggedInUser", email);
          message.className = "success-message";
          message.innerText = "✅ Login successful. Redirecting...";
          setTimeout(() => {
            window.location.href = "AIGNUS.htm";
          }, 1500);
        } else {
          message.className = "error-message";
          message.innerText = data.error || "❌ Invalid email or password";
        }
      })
      .catch(err => {
        message.className = "error-message";
        message.innerText = "❌ Login failed. Please try again.";
      });
    });
  }

  // ✅ Signup Logic
  const signupForm = document.getElementById("signupForm");
  if (signupForm) {
    const passwordInput = document.getElementById("password");
    const confirmPasswordInput = document.getElementById("confirm-password");
    const strengthMeter = document.getElementById("strengthMeter");

    passwordInput.addEventListener("input", function () {
      const password = passwordInput.value;
      let strength = 0;

      if (password.length >= 8) strength += 25;
      if (password.length >= 12) strength += 25;
      if (/[A-Z]/.test(password)) strength += 15;
      if (/[0-9]/.test(password)) strength += 15;
      if (/[^A-Za-z0-9]/.test(password)) strength += 20;

      if (strength > 100) strength = 100;

      strengthMeter.style.width = strength + "%";
      strengthMeter.style.backgroundColor =
        strength < 40
          ? getComputedStyle(document.documentElement).getPropertyValue("--warning")
          : strength < 70
          ? getComputedStyle(document.documentElement).getPropertyValue("--accent")
          : getComputedStyle(document.documentElement).getPropertyValue("--success");
    });

    signupForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const fullName = document.getElementById("fullname").value.trim();
      const email = document.getElementById("email").value.trim();
      const password = passwordInput.value;
      const confirmPassword = confirmPasswordInput.value;

      if (password !== confirmPassword) {
        alert("❌ Passwords do not match!");
        return;
      }

      fetch('auth.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=signup&name=${encodeURIComponent(fullName)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          localStorage.setItem("loggedInUser", data.email);
          window.location.href = "AIGNUS.htm";
          
          signupForm.style.display = "none";
          document.querySelector(".signup-header").innerHTML = `
            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
              <circle cx="26" cy="26" r="25" fill="none" stroke="var(--success)" stroke-width="4"/>
              <path fill="none" stroke="var(--success)" stroke-width="4" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
            </svg>
            <h1>Welcome aboard!</h1>
            <p>Your account has been created successfully</p>`;

          document.querySelector(".signup-footer").innerHTML =
            `Redirecting to home page in <span id="countdown">3</span> seconds...`;

          let count = 3;
          const countdown = setInterval(() => {
            count--;
            document.getElementById("countdown").textContent = count;
            if (count <= 0) {
              clearInterval(countdown);
              window.location.href = "AIGNUS.htm";
            }
          }, 1000);
        } else {
          alert(data.error || "⚠️ Signup failed. Please try again.");
        }
      })
      .catch(err => {
        alert("⚠️ Signup failed. Please try again.");
      });
    });
  }
});

// Add this to auth.js
document.getElementById('password')?.addEventListener('input', function() {
    const password = this.value;
    const meter = document.getElementById('strengthMeter');
    let strength = 0;
    
    if (password.length >= 8) strength += 25;
    if (password.length >= 12) strength += 25;
    if (/[A-Z]/.test(password)) strength += 15;
    if (/[0-9]/.test(password)) strength += 15;
    if (/[^A-Za-z0-9]/.test(password)) strength += 20;

    meter.style.width = strength + '%';
    meter.style.backgroundColor =
        strength < 40 ? '#facc15' :  // yellow
        strength < 70 ? '#10b981' :  // teal
        '#22c55e';                   // green
});