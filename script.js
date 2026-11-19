
document.addEventListener("DOMContentLoaded", function () {

    // =====================================
    // 1. MOBILE NAVIGATION
    // =====================================

    const menuToggle = document.getElementById("menu-toggle");
    const navLinks = document.getElementById("nav-links");

    if (menuToggle && navLinks) {

        menuToggle.addEventListener("click", function () {

            navLinks.classList.toggle("active");

            const isOpen = navLinks.classList.contains("active");

            menuToggle.textContent = isOpen ? "✕" : "☰";
            menuToggle.setAttribute("aria-expanded", isOpen);

        });

        document.querySelectorAll(".nav-links a").forEach(function (link) {

            link.addEventListener("click", function () {

                navLinks.classList.remove("active");
                menuToggle.textContent = "☰";
                menuToggle.setAttribute("aria-expanded", "false");

            });

        });

    }


    // =====================================
    // 2. DATABASE GALLERY + LIGHTBOX
    // =====================================

    const galleryContainer = document.querySelector(".gallery-container");

    const lightbox = document.getElementById("lightbox");
    const lightboxImage = document.getElementById("lightbox-image");
    const closeLightbox = document.getElementById("close-lightbox");


    // Load gallery photos from PostgreSQL
    async function loadGallery() {

        if (!galleryContainer) return;

        try {

            const response = await fetch("api/gallery.php", {
                method: "GET",
                cache: "no-store"
            });

            if (!response.ok) {
                throw new Error("Could not retrieve gallery photos.");
            }

            const data = await response.json();

            if (!data.success || !Array.isArray(data.photos)) {
                throw new Error("Invalid gallery data received.");
            }

            // Remove existing sample photos
            galleryContainer.innerHTML = "";

            if (data.photos.length === 0) {

                const message = document.createElement("p");

                message.className = "gallery-message";
                message.textContent = "No gallery photos have been uploaded yet.";

                galleryContainer.appendChild(message);

                return;
            }

            // Generate gallery cards
            data.photos.forEach(function (photo) {

                const galleryItem = document.createElement("div");
                galleryItem.className = "gallery-item";

                const image = document.createElement("img");

                image.src = photo.image_path;
                image.alt = photo.caption || "Kisumu Lakeside gallery photo";
                image.loading = "lazy";

                const overlay = document.createElement("div");
                overlay.className = "gallery-overlay";

                const caption = document.createElement("h3");

                caption.textContent = photo.caption || "KISUMU LAKESIDE";

                overlay.appendChild(caption);

                galleryItem.appendChild(image);
                galleryItem.appendChild(overlay);

                galleryContainer.appendChild(galleryItem);

            });

        } catch (error) {

            console.error("Gallery loading error:", error);

            // Existing sample photos remain if the API fails.

        }

    }


    // Open dynamically loaded gallery images
    if (galleryContainer && lightbox && lightboxImage) {

        galleryContainer.addEventListener("click", function (event) {

            const image = event.target.closest(".gallery-item img");

            if (!image) return;

            lightboxImage.src = image.src;
            lightboxImage.alt = image.alt;

            lightbox.classList.add("active");

        });

    }


    // Close gallery lightbox
    if (lightbox && closeLightbox) {

        closeLightbox.addEventListener("click", function () {

            lightbox.classList.remove("active");

        });

        lightbox.addEventListener("click", function (event) {

            if (event.target === lightbox) {
                lightbox.classList.remove("active");
            }

        });

        document.addEventListener("keydown", function (event) {

            if (event.key === "Escape") {
                lightbox.classList.remove("active");
            }

        });

    }


    // Start loading gallery
    loadGallery();


    // =====================================
    // 3. DATABASE FIXTURE MANAGEMENT
    // =====================================

    const matchContainer = document.getElementById("match-container");
    const filterButtons = document.querySelectorAll(".filter-btn");
    const matchSearch = document.getElementById("match-search");

    let matches = [];


    // Format match date
    function formatMatchDate(dateValue) {

        if (!dateValue) {
            return "To be announced";
        }

        const date = new Date(dateValue + "T00:00:00");

        if (isNaN(date.getTime())) {
            return "Date not available";
        }

        return date.toLocaleDateString("en-KE", {
            day: "numeric",
            month: "long",
            year: "numeric"
        });

    }


    // Safely create text elements
    function createTextElement(tag, className, text) {

        const element = document.createElement(tag);

        if (className) {
            element.className = className;
        }

        element.textContent = text;

        return element;

    }


    // Display fixtures
    function displayMatches(filter) {

        if (!matchContainer) return;

        matchContainer.innerHTML = "";

        const searchTerm = matchSearch
            ? matchSearch.value.toLowerCase().trim()
            : "";

        const filteredMatches = matches.filter(function (match) {

            const selectedStatus =
                filter === "all" ||
                (filter === "results" && match.status === "completed") ||
                match.status === filter;

            const searchableText = [
                match.home_team,
                match.away_team,
                match.venue
            ].join(" ").toLowerCase();

            const matchesSearch = searchableText.includes(searchTerm);

            return selectedStatus && matchesSearch;

        });


        if (filteredMatches.length === 0) {

            matchContainer.appendChild(
                createTextElement(
                    "p",
                    "match-details",
                    "No matches found."
                )
            );

            return;

        }


        filteredMatches.forEach(function (match) {

            const matchCard = document.createElement("div");

            matchCard.classList.add("match-card", match.status);


            // Status label
            const statusLabels = {
                upcoming: "UPCOMING",
                completed: "RESULT",
                postponed: "POSTPONED",
                cancelled: "CANCELLED"
            };

            const statusLabel = createTextElement(
                "span",
                "match-status",
                statusLabels[match.status] || match.status.toUpperCase()
            );

            matchCard.appendChild(statusLabel);


            // Match heading
            let heading = "UPCOMING GAME";

            if (match.status === "completed") {
                heading = "PREVIOUS GAME";
            } else if (match.status === "postponed") {
                heading = "POSTPONED GAME";
            } else if (match.status === "cancelled") {
                heading = "CANCELLED GAME";
            }

            matchCard.appendChild(
                createTextElement("h3", "", heading)
            );


            // Teams section
            const teamsContainer = document.createElement("div");
            teamsContainer.className = "match-teams";


            // Home team
            const homeTeam = document.createElement("div");

            homeTeam.appendChild(
                createTextElement("h2", "", "KL")
            );

            homeTeam.appendChild(
                createTextElement("p", "", match.home_team)
            );


            // Score or VS
            let scoreText = "VS";

            if (match.status === "completed") {

                if (
                    match.home_score !== null &&
                    match.away_score !== null
                ) {

                    scoreText =
                        match.home_score + " - " + match.away_score;

                } else {

                    scoreText = "Result pending";

                }

            } else if (match.status === "postponed") {

                scoreText = "POSTPONED";

            } else if (match.status === "cancelled") {

                scoreText = "CANCELLED";

            }

            const versus = createTextElement(
                "span",
                "versus",
                scoreText
            );


            // Away team
            const awayTeam = document.createElement("div");

            awayTeam.appendChild(
                createTextElement("h2", "", "OPP")
            );

            awayTeam.appendChild(
                createTextElement("p", "", match.away_team)
            );


            teamsContainer.appendChild(homeTeam);
            teamsContainer.appendChild(versus);
            teamsContainer.appendChild(awayTeam);

            matchCard.appendChild(teamsContainer);


            // Date
            matchCard.appendChild(
                createTextElement(
                    "p",
                    "match-details",
                    "Date: " + formatMatchDate(match.match_date)
                )
            );


            // Venue
            matchCard.appendChild(
                createTextElement(
                    "p",
                    "match-details",
                    "Venue: " + (match.venue || "To be announced")
                )
            );


            matchContainer.appendChild(matchCard);

        });

    }


    // Fetch fixtures from PostgreSQL
    async function loadMatches() {

        if (!matchContainer) return;

        matchContainer.innerHTML = "";

        matchContainer.appendChild(
            createTextElement(
                "p",
                "match-details",
                "Loading fixtures..."
            )
        );

        try {

            const response = await fetch("api/fixtures.php", {
                method: "GET",
                cache: "no-store"
            });

            if (!response.ok) {
                throw new Error("Could not retrieve fixtures.");
            }

            const data = await response.json();

            if (!data.success || !Array.isArray(data.fixtures)) {
                throw new Error("Invalid fixture data received.");
            }

            matches = data.fixtures;

            displayMatches("all");

        } catch (error) {

            console.error("Fixture loading error:", error);

            matchContainer.innerHTML = "";

            matchContainer.appendChild(
                createTextElement(
                    "p",
                    "match-details",
                    "Unable to load fixtures. Please try again later."
                )
            );

        }

    }


    // Start loading fixtures
    if (matchContainer) {
        loadMatches();
    }


    // =====================================
    // 4. FIXTURE FILTER BUTTONS
    // =====================================

    filterButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            const selectedFilter = button.dataset.filter;

            displayMatches(selectedFilter);

            filterButtons.forEach(function (btn) {
                btn.classList.remove("active");
            });

            button.classList.add("active");

        });

    });


    // =====================================
    // 5. FIXTURE SEARCH
    // =====================================

    if (matchSearch) {

        matchSearch.addEventListener("input", function () {

            const activeButton =
                document.querySelector(".filter-btn.active");

            const currentFilter = activeButton
                ? activeButton.dataset.filter
                : "all";

            displayMatches(currentFilter);

        });

    }


    // =====================================
    // 6. DATABASE PLAYER MANAGEMENT
    // =====================================

    const playerModal = document.getElementById("player-modal");

    const closePlayerModal =
        document.getElementById("close-player-modal");

    const modalPlayerImage =
        document.getElementById("modal-player-image");

    const modalPlayerName =
        document.getElementById("modal-player-name");

    const modalPlayerPosition =
        document.getElementById("modal-player-position");

    const modalPlayerBio =
        document.getElementById("modal-player-bio");

    const modalPlayerNumber =
        document.getElementById("modal-player-number");


    // Find existing player card template
    const existingPlayerCards =
        document.querySelectorAll(".player-card");

    if (existingPlayerCards.length > 0) {

        const playerContainer = existingPlayerCards[0].parentElement;

        const playerTemplate = existingPlayerCards[0].cloneNode(true);


        // Remove old sample player cards
        existingPlayerCards.forEach(function (card) {
            card.remove();
        });


        // Display player messages
        function showPlayerMessage(message) {

            playerContainer.querySelectorAll(".player-message")
                .forEach(function (element) {
                    element.remove();
                });

            const paragraph = document.createElement("p");

            paragraph.className = "player-message";
            paragraph.textContent = message;

            playerContainer.appendChild(paragraph);

        }


        // Open player profile
        function openPlayerProfile(player) {

            if (!playerModal) return;

            const jersey = String(player.jersey_number).padStart(2, "0");

            if (modalPlayerImage) {

                if (player.photo_path) {
                    modalPlayerImage.src = player.photo_path;
                } else {
                    modalPlayerImage.removeAttribute("src");
                }

                modalPlayerImage.alt = player.full_name;

            }

            if (modalPlayerName) {
                modalPlayerName.textContent = player.full_name;
            }

            if (modalPlayerPosition) {
                modalPlayerPosition.textContent =
                    player.position || "Position not specified";
            }

            if (modalPlayerNumber) {
                modalPlayerNumber.textContent = "Jersey #" + jersey;
            }

            if (modalPlayerBio) {
                modalPlayerBio.textContent =
                    player.biography || "Player biography coming soon.";
            }


            // Player statistics
            const stats = {
                ppg: player.ppg,
                rpg: player.rpg,
                apg: player.apg,
                spg: player.spg
            };

            const statElements = {
                ppg: document.getElementById("modal-ppg"),
                rpg: document.getElementById("modal-rpg"),
                apg: document.getElementById("modal-apg"),
                spg: document.getElementById("modal-spg")
            };

            Object.keys(statElements).forEach(function (key) {

                if (statElements[key]) {

                    statElements[key].textContent =
                        Number(stats[key] || 0).toFixed(2);

                }

            });

            playerModal.classList.add("active");

        }


        // Fetch players from PHP API
        async function loadPlayers() {

            try {

                showPlayerMessage("Loading players...");

                const response = await fetch("api/players.php", {
                    method: "GET",
                    cache: "no-store"
                });

                if (!response.ok) {
                    throw new Error("Could not retrieve players.");
                }

                const data = await response.json();

                if (!data.success || !Array.isArray(data.players)) {
                    throw new Error("Invalid player data received.");
                }


                // Remove loading message
                playerContainer.querySelectorAll(".player-message")
                    .forEach(function (element) {
                        element.remove();
                    });


                if (data.players.length === 0) {

                    showPlayerMessage(
                        "No players have been registered yet."
                    );

                    return;

                }


                // Generate player cards
                data.players.forEach(function (player) {

                    const card = playerTemplate.cloneNode(true);

                    const image = card.querySelector("img");

                    const name = card.querySelector(".player-info h3");

                    const position = card.querySelector(".player-info p");

                    const number = card.querySelector(".player-number");


                    if (name) {
                        name.textContent = player.full_name;
                    }

                    if (position) {
                        position.textContent =
                            player.position || "Position not specified";
                    }

                    if (number) {
                        number.textContent =
                            String(player.jersey_number).padStart(2, "0");
                    }

                    if (image) {

                        if (player.photo_path) {
                            image.src = player.photo_path;
                        } else {
                            image.removeAttribute("src");
                        }

                        image.alt = player.full_name;

                    }


                    // Store player ID
                    card.dataset.playerId = player.id;


                    // Open player profile
                    card.addEventListener("click", function () {
                        openPlayerProfile(player);
                    });

                    playerContainer.appendChild(card);

                });

            } catch (error) {

                console.error("Player loading error:", error);

                showPlayerMessage(
                    "Unable to load players. Please try again later."
                );

            }

        }


        // Start loading players
        loadPlayers();

    }


    // =====================================
    // 7. CLOSE PLAYER PROFILE
    // =====================================

    if (playerModal && closePlayerModal) {

        closePlayerModal.addEventListener("click", function () {

            playerModal.classList.remove("active");

        });

        playerModal.addEventListener("click", function (event) {

            if (event.target === playerModal) {
                playerModal.classList.remove("active");
            }

        });

        document.addEventListener("keydown", function (event) {

            if (event.key === "Escape") {
                playerModal.classList.remove("active");
            }

        });

    }



// =====================================
// 8. LOAD NEWS FROM DATABASE
// =====================================

async function loadNews() {
    const newsContainer = document.querySelector(".news-container");

    if (!newsContainer) return;

    try {
        const response = await fetch("api/news.php");
        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error("Unable to load news.");
        }

        newsContainer.innerHTML = "";

        if (data.articles.length === 0) {
            newsContainer.innerHTML = `
                <p class="no-news">
                    No news articles have been published yet.
                    Check back soon!
                </p>
            `;
            return;
        }

        data.articles.forEach(article => {

            const card = document.createElement("div");
            card.className = "news-card";

            // News image
            if (article.image_path) {
                const imageWrapper = document.createElement("div");
                imageWrapper.className = "news-image";

                const image = document.createElement("img");
                image.src = article.image_path;
                image.alt = article.title;

                imageWrapper.appendChild(image);
                card.appendChild(imageWrapper);
            }

            // News content
            const content = document.createElement("div");
            content.className = "news-content";

            const date = document.createElement("span");
            date.textContent = article.published_at
                ? new Date(article.published_at).toLocaleDateString()
                : "Club Update";

            const title = document.createElement("h3");
            title.textContent = article.title;

            const description = document.createElement("p");

            description.textContent =
                article.content.length > 150
                    ? article.content.substring(0, 150) + "..."
                    : article.content;

            content.appendChild(date);
            content.appendChild(title);
            content.appendChild(description);

            card.appendChild(content);

            newsContainer.appendChild(card);
        });

    } catch (error) {
        console.error("News loading error:", error);

        newsContainer.innerHTML = `
            <p>Unable to load news at the moment.</p>
        `;
    }
}

document.addEventListener("DOMContentLoaded", loadNews);


});
