// Events on login and register: show or hide password, and check the form before it is sent.
document.querySelectorAll(".toggle-password").forEach((button) => {
    button.addEventListener("click", () => {
        const input = document.getElementById(button.dataset.target);
        if (!input) {
            return;
        }
        const show = input.type === "password";
        input.type = show ? "text" : "password";
        button.textContent = show ? "Hide" : "Show";
        button.setAttribute("aria-label", show ? "Hide password" : "Show password");
    });
});

const form = document.getElementById("auth-form");
if (form) {
    form.addEventListener("submit", (event) => {
        const errors = validateAuthForm(form);
        if (errors.length === 0) {
            return;
        }
        event.preventDefault();
        showFormErrors(form, errors);
    });
}

function validateAuthForm(form) {
    const errors = [];
    const username = form.querySelector("#username");
    const email = form.querySelector("#email");
    const password = form.querySelector("#password");
    const confirm = form.querySelector("#confirm_password");

    if (username) {
        const value = username.value.trim();
        if (value === "") {
            errors.push("Please enter a username");
        } else if (value.length < 3) {
            errors.push("Username must be at least 3 characters");
        }
    }

    if (email) {
        const value = email.value.trim();
        if (value === "") {
            errors.push("Please enter your email");
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            errors.push("Please enter a valid email");
        }
    }

    if (password) {
        if (password.value === "") {
            errors.push("Please enter your password");
        } else if (password.value.length < 6) {
            errors.push("Password must be at least 6 characters");
        }
    }

    if (confirm) {
        if (confirm.value === "") {
            errors.push("Please confirm your password");
        } else if (password && confirm.value !== password.value) {
            errors.push("Passwords do not match");
        }
    }

    return errors;
}

function showFormErrors(form, errors) {
    let box = form.previousElementSibling;
    if (!box || !box.classList.contains("form-errors")) {
        box = document.createElement("div");
        box.className = "form-errors";
        box.setAttribute("role", "alert");
        form.before(box);
    }
    box.replaceChildren();
    errors.forEach((message) => {
        const line = document.createElement("p");
        line.textContent = message;
        box.appendChild(line);
    });
}
