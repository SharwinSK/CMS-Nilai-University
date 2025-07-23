function saveProfile() {
  const name = document.getElementById("name").value.trim();
  const email = document.getElementById("email").value.trim();

  if (!name || !email || !isValidEmail(email)) {
    alert("Please enter valid name and email.");
    return;
  }

  fetch("AdvisorProfile.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `ajax=1&name=${encodeURIComponent(name)}&email=${encodeURIComponent(
      email
    )}`,
  })
    .then((response) => {
      console.log("Raw response:", response);
      return response.json(); // try to parse JSON
    })
    .then((data) => {
      console.log("Parsed data:", data);
      if (data.success) {
        // ✅ Update visible fields
        document.getElementById("displayName").textContent = name;
        document.getElementById("displayEmail").textContent = email;

        // ✅ Update navbar dropdown
        const header = document.querySelector(".dropdown-toggle");
        if (header) {
          header.innerHTML = `<i class="fas fa-user-circle me-2"></i>${name}`;
        }

        // ✅ Close modal
        const modal = bootstrap.Modal.getInstance(
          document.getElementById("editProfileModal")
        );
        if (modal) modal.hide();

        // ✅ Show success alert
        showSuccessAlert();
      } else {
        alert("Failed to update profile. Try again.");
      }
    })
    .catch((error) => {
      console.error("Fetch Error:", error);
      alert("Error connecting to server.");
    });
}

function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

function showSuccessAlert() {
  const alert = document.getElementById("successAlert");
  alert.style.display = "block";
  alert.classList.add("show");

  // Auto hide after 3 seconds
  setTimeout(() => {
    hideAlert();
  }, 3000);
}

function hideAlert() {
  const alert = document.getElementById("successAlert");
  alert.classList.remove("show");
  setTimeout(() => {
    alert.style.display = "none";
  }, 150);
}
