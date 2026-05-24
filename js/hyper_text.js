
// ========================================
// 👾 HYPER TEXT EFFECT (Scramble)
// ========================================
const letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789#%&@$";

document.querySelectorAll(".hyper-text").forEach(element => {
    // Init: Save original text if not in data-text (fallback)
    if (!element.dataset.text) element.dataset.text = element.innerText;

    element.onmouseover = event => {
        let iteration = 0;
        const originalText = event.target.dataset.text;

        clearInterval(event.target.interval);

        event.target.interval = setInterval(() => {
            event.target.innerText = originalText
                .split("")
                .map((letter, index) => {
                    if (index < iteration) {
                        return originalText[index];
                    }
                    return letters[Math.floor(Math.random() * letters.length)];
                })
                .join("");

            if (iteration >= originalText.length) {
                clearInterval(event.target.interval);
            }

            iteration += 1 / 3; // Speed control
        }, 30);
    };
});

// Optional: Trigger once on load for effect
setTimeout(() => {
    document.querySelectorAll(".hyper-text").forEach(el => {
        el.dispatchEvent(new Event('mouseover'));
    });
}, 1500); // Wait for fade in
