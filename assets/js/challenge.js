// Events in a challenge: Start click, digit click, keys 0-9, and a one-second countdown timer.
const page = document.querySelector(".game-page");
const apiUrl = page.dataset.api;
const difficulty = page.dataset.difficulty;
const loading = document.getElementById("game-loading");
const errorBox = document.getElementById("game-error");
const errorText = document.getElementById("game-error-text");
const play = document.getElementById("game-play");
const finished = document.getElementById("game-finished");
const image = document.getElementById("puzzle-image");
const resultText = document.getElementById("result-text");
const answerText = document.getElementById("answer-text");
const buttons = Array.from(document.querySelectorAll(".answer"));
const timerFill = document.getElementById("timer-fill");
const timerLabel = document.getElementById("timer-label");

let locked = true;
let resultTimer = null;
let countdownId = null;
let timeLimit = 20;

document.getElementById("retry-game")?.addEventListener("click", () => beginMatch());
buttons.forEach((button) => {
    button.addEventListener("click", () => submitAnswer(Number(button.dataset.answer), false));
});

document.addEventListener("keydown", (event) => {
    if (event.repeat || event.metaKey || event.ctrlKey || event.altKey) {
        return;
    }
    if (event.target instanceof HTMLInputElement || event.target instanceof HTMLTextAreaElement) {
        return;
    }
    if (/^[0-9]$/.test(event.key)) {
        submitAnswer(Number(event.key), false);
    }
});

beginMatch();

async function beginMatch() {
    window.clearTimeout(resultTimer);
    stopTimer();
    locked = true;
    showOnly(loading);

    try {
        const started = await postJson({ action: "start", difficulty });
        timeLimit = started.time_limit;
        updateHud(started);
        await loadPuzzle();
    } catch (error) {
        showError(error.message);
    }
}

async function loadPuzzle() {
    window.clearTimeout(resultTimer);
    stopTimer();
    locked = true;
    clearButtons();
    showOnly(loading);

    try {
        const data = await getJson("?action=puzzle");
        if (!isSafeImage(data.image)) {
            throw new Error("The puzzle image could not be loaded.");
        }
        image.src = data.image;
        timeLimit = data.time_limit;
        updateHud(data);
        showOnly(play);
        locked = false;
        startTimer(data.remaining);
    } catch (error) {
        showError(error.message);
    }
}

async function submitAnswer(answer, timeout) {
    if (locked) {
        return;
    }
    if (!timeout && (!Number.isInteger(answer) || answer < 0 || answer > 9)) {
        return;
    }

    locked = true;
    stopTimer();
    clearButtons();
    if (!timeout) {
        buttons.find((button) => Number(button.dataset.answer) === answer)?.classList.add("is-selected");
    }
    buttons.forEach((button) => {
        button.disabled = true;
    });

    try {
        const data = await postJson(timeout ? { action: "answer", timeout: true } : { action: "answer", answer });
        showResult(answer, data);
        updateHud(data);
        resultTimer = window.setTimeout(() => {
            if (data.finished) {
                showFinished(data);
            } else {
                loadPuzzle();
            }
        }, 1200);
    } catch (error) {
        showError(error.message);
    }
}

function showResult(answer, data) {
    let message = "Wrong!";
    if (data.timed_out) {
        message = "Time ran out";
    } else if (data.correct) {
        message = `Correct! +${data.points}`;
    }
    resultText.textContent = message;
    resultText.classList.remove("hidden", "ok", "bad");
    resultText.classList.add(data.correct ? "ok" : "bad");
    answerText.textContent = data.correct ? `Streak ${data.streak}` : `Correct answer: ${data.correct_answer}`;
    answerText.classList.remove("hidden");

    buttons.forEach((button) => {
        const value = Number(button.dataset.answer);
        button.classList.remove("is-selected", "is-correct", "is-wrong");
        if (value === data.correct_answer) {
            button.classList.add("is-correct");
        } else if (!data.timed_out && value === answer) {
            button.classList.add("is-wrong");
        }
    });
}

function showFinished(data) {
    stopTimer();
    document.getElementById("finish-title").textContent = data.reason === "complete" ? "Match complete" : "Out of lives";
    document.getElementById("finish-reason").textContent = data.reason === "complete"
        ? "You finished every round."
        : "The match ended when the last life was lost.";
    document.getElementById("finish-score").textContent = String(data.score);
    document.getElementById("finish-correct").textContent = `${data.correct_count}/${data.round_limit}`;
    document.getElementById("finish-best").textContent = String(data.best_score);
    showOnly(finished);
}

function updateHud(data) {
    document.getElementById("round-label").textContent = `Round ${data.round}/${data.round_limit}`;
    document.getElementById("lives-label").textContent = `${"♥".repeat(data.lives)}${"♡".repeat(Math.max(0, data.max_lives - data.lives))}`;
    document.getElementById("streak-label").textContent = `Streak ${data.streak}`;
    document.getElementById("stat-score").textContent = String(data.score);
    document.getElementById("stat-correct").textContent = String(data.correct_count);
    document.getElementById("stat-streak").textContent = String(data.streak);
}

function startTimer(seconds) {
    stopTimer();
    const total = Math.max(timeLimit, 1);
    const started = performance.now();
    const initial = Math.max(0, seconds);
    renderTimer(initial, total);
    if (initial <= 0) {
        submitAnswer(null, true);
        return;
    }
    countdownId = window.setInterval(() => {
        const elapsed = (performance.now() - started) / 1000;
        const left = Math.max(0, initial - elapsed);
        renderTimer(left, total);
        if (left <= 0) {
            stopTimer();
            submitAnswer(null, true);
        }
    }, 100);
}

function renderTimer(seconds, total) {
    const ratio = total > 0 ? Math.max(0, Math.min(1, seconds / total)) : 0;
    timerFill.style.width = `${ratio * 100}%`;
    timerFill.classList.toggle("danger", ratio < 0.3);
    timerLabel.textContent = `${Math.ceil(seconds)}s`;
}

function stopTimer() {
    if (countdownId !== null) {
        window.clearInterval(countdownId);
        countdownId = null;
    }
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
    [loading, errorBox, play, finished].forEach((element) => element.classList.add("hidden"));
    section.classList.remove("hidden");
}

function showError(message) {
    stopTimer();
    errorText.textContent = message || "Failed to load game";
    showOnly(errorBox);
    locked = true;
}

async function postJson(body) {
    const response = await fetch(apiUrl, {
        method: "POST",
        headers: { Accept: "application/json", "Content-Type": "application/json" },
        body: JSON.stringify(body),
    });
    return readResponse(response);
}

async function getJson(query) {
    const response = await fetch(`${apiUrl}${query}`, { headers: { Accept: "application/json" } });
    return readResponse(response);
}

async function readResponse(response) {
    if (response.status === 401) {
        window.location.href = "login.php";
        throw new Error("Please sign in again.");
    }
    const data = await response.json();
    if (!response.ok || data.status !== "success") {
        throw new Error(data.message || "The challenge could not continue.");
    }
    return data;
}

function isSafeImage(url) {
    try {
        const parsed = new URL(url);
        return parsed.protocol === "https:" && ["www.sanfoh.com", "sanfoh.com", "marcconrad.com", "www.marcconrad.com"].includes(parsed.hostname);
    } catch (error) {
        return false;
    }
}
