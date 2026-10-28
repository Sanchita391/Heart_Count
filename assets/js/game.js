// Events on the practice screen: Refresh click, Retry click, digit button click, and keys 0-9.
const page = document.querySelector(".game-page");
const apiUrl = page ? page.dataset.api : "";
const loading = document.getElementById("game-loading");
const errorBox = document.getElementById("game-error");
const errorText = document.getElementById("game-error-text");
const play = document.getElementById("game-play");
const image = document.getElementById("puzzle-image");
const sourceEl = document.getElementById("puzzle-source");
const resultText = document.getElementById("result-text");
const answerText = document.getElementById("answer-text");
const scoreEl = document.getElementById("stat-score");
const gamesEl = document.getElementById("stat-games");
const accuracyEl = document.getElementById("stat-accuracy");
const buttons = Array.from(document.querySelectorAll(".answer"));

let locked = false;
let resultTimer = null;

document.getElementById("refresh-game")?.addEventListener("click", () => loadPuzzle());
document.getElementById("retry-game")?.addEventListener("click", () => loadPuzzle());

buttons.forEach((button) => {
    button.addEventListener("click", () => submitAnswer(Number(button.dataset.answer)));
});

document.addEventListener("keydown", (event) => {
    if (event.repeat || event.metaKey || event.ctrlKey || event.altKey) {
        return;
    }
    if (event.target instanceof HTMLInputElement || event.target instanceof HTMLTextAreaElement) {
        return;
    }
    if (/^[0-9]$/.test(event.key)) {
        submitAnswer(Number(event.key));
    }
});

loadPuzzle();

async function loadPuzzle() {
    window.clearTimeout(resultTimer);
    locked = true;
    showOnly(loading);
    clearButtons();

    try {
        const response = await fetch(`${apiUrl}?action=new`, {
            headers: { Accept: "application/json" },
        });
        if (response.status === 401) {
            window.location.href = "login.php";
            return;
        }
        const data = await response.json();
        if (!response.ok || data.status !== "success" || !isSafeImage(data.image)) {
            throw new Error(data.message || "Failed to load game");
        }
        image.src = data.image;
        if (sourceEl) {
            sourceEl.textContent = data.caption || "Heart API · answer checked on the server";
        }
        updateStats(data);
        showOnly(play);
        locked = false;
    } catch (error) {
        errorText.textContent = error.message || "Failed to load game";
        showOnly(errorBox);
        locked = true;
    }
}

async function submitAnswer(answer) {
    if (locked || !Number.isInteger(answer) || answer < 0 || answer > 9) {
        return;
    }

    locked = true;
    clearButtons();
    const selected = buttons.find((button) => Number(button.dataset.answer) === answer);
    selected?.classList.add("is-selected");
    buttons.forEach((button) => {
        button.disabled = true;
    });

    try {
        const response = await fetch(apiUrl, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ answer }),
        });
        if (response.status === 401) {
            window.location.href = "login.php";
            return;
        }
        const data = await response.json();
        if (!response.ok || data.status !== "success") {
            throw new Error(data.message || "Your answer could not be saved.");
        }

        showResult(answer, data);
        updateStats(data);
        resultTimer = window.setTimeout(loadPuzzle, 1000);
    } catch (error) {
        errorText.textContent = error.message || "Your answer could not be saved.";
        showOnly(errorBox);
    }
}

function showResult(answer, data) {
    const correct = Boolean(data.correct);
    resultText.textContent = correct ? "Correct!" : "Wrong!";
    resultText.classList.remove("hidden", "ok", "bad");
    resultText.classList.add(correct ? "ok" : "bad");

    if (!correct) {
        answerText.textContent = `Correct answer: ${data.correct_answer}`;
        answerText.classList.remove("hidden");
    } else {
        answerText.classList.add("hidden");
    }

    buttons.forEach((button) => {
        const value = Number(button.dataset.answer);
        button.classList.remove("is-selected", "is-correct", "is-wrong");
        if (value === data.correct_answer) {
            button.classList.add("is-correct");
        } else if (value === answer) {
            button.classList.add("is-wrong");
        }
    });
}

function updateStats(data) {
    if (scoreEl) scoreEl.textContent = String(data.score);
    if (gamesEl) gamesEl.textContent = String(data.total);
    if (accuracyEl) accuracyEl.textContent = `${data.accuracy}%`;
}

function clearButtons() {
    resultText.classList.add("hidden");
    answerText.classList.add("hidden");
    buttons.forEach((button) => {
        button.disabled = false;
        button.classList.remove("is-selected", "is-correct", "is-wrong");
    });
}

function showOnly(section) {
    [loading, errorBox, play].forEach((element) => {
        element?.classList.add("hidden");
    });
    section?.classList.remove("hidden");
}

function isSafeImage(url) {
    try {
        const parsed = new URL(url);
        return parsed.protocol === "https:" && ["www.sanfoh.com", "sanfoh.com", "marcconrad.com", "www.marcconrad.com"].includes(parsed.hostname);
    } catch (error) {
        return false;
    }
}
