<?php
/**
 * Sezione Tech News - davidefiore.com
 * Visualizzazione dinamica ed interattiva delle notizie tecnologiche
 * Caratteristiche premium WAHU!: Filtri fluidi, Caricamento staggered, Lettore TTS AI, Barra progresso
 */

require_once __DIR__ . '/api/db.php';

// Formattatore di date in Italiano
function format_italian_date($date_str) {
    $timestamp = strtotime($date_str);
    if (!$timestamp) return $date_str;
    
    $months = [
        1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile', 5 => 'Maggio', 6 => 'Giugno',
        7 => 'Luglio', 8 => 'Agosto', 9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
    ];
    
    $day = date('j', $timestamp);
    $month_num = (int)date('n', $timestamp);
    $year = date('Y', $timestamp);
    
    return "{$day} {$months[$month_num]} {$year}";
}

// Assegnazione automatica intelligente della categoria in base al contenuto (Zero allucinazioni)
function get_article_category($title, $content) {
    $text = mb_strtolower($title . ' ' . $content, 'UTF-8');
    
    if (mb_strpos($text, 'ai') !== false || mb_strpos($text, 'intelligenza') !== false || mb_strpos($text, 'chatbot') !== false || mb_strpos($text, 'rag') !== false || mb_strpos($text, 'modelli') !== false) {
        return 'AI & RAG';
    }
    if (mb_strpos($text, 'cybersecurity') !== false || mb_strpos($text, 'sicurezza') !== false || mb_strpos($text, 'hacker') !== false || mb_strpos($text, 'difesa') !== false || mb_strpos($text, 'phishing') !== false) {
        return 'CyberSecurity';
    }
    if (mb_strpos($text, 'next.js') !== false || mb_strpos($text, 'frontend') !== false || mb_strpos($text, 'web') !== false || mb_strpos($text, 'rendering') !== false || mb_strpos($text, 'app') !== false) {
        return 'Web Dev';
    }
    return 'Tech Trend';
}

// Recupera le notizie dal database SQLite
$articles = [];
$error_msg = null;

try {
    $db = get_db_connection();
    $stmt = $db->query("SELECT * FROM tech_news WHERE is_published = 1 ORDER BY created_at DESC");
    $articles = $stmt->fetchAll();
} catch (Exception $e) {
    $error_msg = "Impossibile caricare le news in questo momento.";
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tech News & Insight | Davide Fiore</title>
    <meta name="description"
        content="Notizie tecnologiche quotidiane ed insights sull'intelligenza artificiale, cybersecurity e sviluppo software a cura di Davide Fiore.">
    <meta name="keywords" content="Tech News, Intelligenza Artificiale, CyberSecurity, Sviluppo Software, Davide Fiore, Aruba, SQLite, PHP">
    <link rel="canonical" href="https://www.davidefiore.com/news.php">
    <link rel="icon" type="image/png" href="assets/favicon.png">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css?v=25">
    <link rel="stylesheet" href="css/news.css?v=25">

    <!-- Google Consent Mode v2 & GA4 -->
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('consent', 'default', {
            'ad_storage': 'denied',
            'ad_user_data': 'denied',
            'ad_ads_personalization': 'denied',
            'analytics_storage': 'denied',
            'wait_for_update': 500
        });
    </script>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-JXWG2WN80N" id="ga-script" type="text/plain"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-JXWG2WN80N');
    </script>
</head>

<body>

    <!-- Sfondi Animati Blobs -->
    <div class="background-blobs">
        <div class="blob blob-1" style="background: rgba(139, 92, 246, 0.12);"></div>
        <div class="blob blob-2" style="background: rgba(217, 70, 239, 0.04);"></div>
        <div class="blob blob-3" style="background: rgba(59, 130, 246, 0.04);"></div>
    </div>

    <!-- Navigazione Glassmorphic -->
    <nav class="glass">
        <div class="logo-container">
            <img src="assets/avatar_new.png" alt="Davide Fiore" class="nav-avatar">
            <div class="logo">Davide Fiore</div>
        </div>
        <div class="nav-links">
            <a href="index.html">Home</a>
            <a href="index.html#about">Chi Sono</a>
            <a href="index.html#services">Servizi</a>
            <a href="news.php" class="active" style="color: #fff; background: rgba(255, 255, 255, 0.08);">News</a>
            <a href="index.html#contact">Contatti</a>
        </div>
    </nav>

    <!-- Page Hero -->
    <section class="container page-hero">
        <span class="tag" style="background: rgba(139, 92, 246, 0.1); color: var(--accent); border-color: rgba(139, 92, 246, 0.3);">DAILY INSIGHTS</span>
        <h1 class="page-title">Tech News & Insight</h1>
        <p class="section-subtitle">Resta aggiornato con l'analisi quotidiana sulle ultime evoluzioni di AI, CyberSecurity e Architetture Software.</p>
    </section>

    <!-- 🟢 BARRA FILTRI CATEGORIA (Apple Segmented Control Style) -->
    <?php if (empty($error_msg) && !empty($articles)): ?>
        <div class="news-filter-container">
            <div class="news-filter-bar glass">
                <button class="filter-btn active" onclick="filterCategory('all')">Tutte</button>
                <button class="filter-btn" onclick="filterCategory('AI & RAG')">AI & RAG</button>
                <button class="filter-btn" onclick="filterCategory('CyberSecurity')">CyberSecurity</button>
                <button class="filter-btn" onclick="filterCategory('Web Dev')">Web Dev</button>
                <button class="filter-btn" onclick="filterCategory('Tech Trend')">Tech Trend</button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Griglia delle News -->
    <main class="container">
        <?php if (!empty($error_msg)): ?>
            <div class="news-empty-state">
                <span class="news-empty-icon" role="img" aria-label="Errore">⚠️</span>
                <h3>Errore di caricamento</h3>
                <p><?= htmlspecialchars($error_msg) ?></p>
            </div>
        <?php elseif (empty($articles)): ?>
            <div class="news-empty-state">
                <span class="news-empty-icon" role="img" aria-label="Nessuna notizia">📡</span>
                <h3>Nessun articolo pubblicato</h3>
                <p>L'intelligenza artificiale di Davide Fiore sta elaborando ed analizzando le ultime notizie tecnologiche. Torna a trovarci presto!</p>
            </div>
        <?php else: ?>
            <div class="news-grid">
                <?php 
                $index = 0;
                foreach ($articles as $article): 
                    $category = get_article_category($article['title'], $article['content']);
                    // Ritardo staggering calcolato
                    $animation_delay = $index * 0.08;
                    $index++;
                ?>
                    <article class="news-card" 
                             data-category="<?= htmlspecialchars($category) ?>" 
                             style="animation-delay: <?= $animation_delay ?>s;"
                             onclick="openNewsModal(<?= htmlspecialchars(json_encode($article), ENT_QUOTES, 'UTF-8') ?>, '<?= htmlspecialchars($category) ?>')" 
                             tabindex="0" 
                             aria-label="Leggi articolo: <?= htmlspecialchars($article['title']) ?>">
                        
                        <div class="news-card-img-wrapper">
                            <?php if (!empty($article['image_url'])): ?>
                                <img src="<?= htmlspecialchars($article['image_url']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" class="news-card-img" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="news-card-img-overlay"></div>
                            <?php endif; ?>
                            
                            <!-- Immagine di fallback con gradiente animato in caso di errore o assenza URL -->
                            <div class="news-fallback-gradient" style="display: <?= empty($article['image_url']) ? 'flex' : 'none' ?>; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(139, 92, 246, 0.3) 0%, rgba(217, 70, 239, 0.2) 100%); justify-content: center; align-items: center; position: absolute; inset: 0;">
                                <span style="font-size: 2rem; opacity: 0.5;">💻</span>
                            </div>
                        </div>

                        <div class="news-card-content">
                            <div class="news-card-meta">
                                <time class="news-card-date" datetime="<?= date('Y-m-d', strtotime($article['created_at'])) ?>">
                                    <?= format_italian_date($article['created_at']) ?>
                                </time>
                                <span class="news-card-tag"><?= htmlspecialchars($category) ?></span>
                            </div>
                            
                            <h2 class="news-card-title"><?= htmlspecialchars($article['title']) ?></h2>
                            
                            <?php if (!empty($article['summary'])): ?>
                                <p class="news-card-summary"><?= htmlspecialchars($article['summary']) ?></p>
                            <?php endif; ?>

                            <div class="news-card-footer">
                                <span class="news-card-cta">Leggi Articolo <span>→</span></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Modal Detail Glassmorphism Overlay -->
    <div class="news-modal" id="news-detail-modal" aria-hidden="true" role="dialog">
        <div class="news-modal-overlay" id="modal-overlay"></div>
        <div class="news-modal-container">
            <!-- 🟥 Barra di Progresso di Lettura (WAHU!) -->
            <div class="modal-progress-container">
                <div class="modal-progress-bar" id="modal-progress"></div>
            </div>

            <button class="news-modal-close-btn" onclick="closeNewsModal()" aria-label="Chiudi finestra">✕</button>
            
            <div id="modal-img-container" style="position: relative;">
                <img id="modal-img" src="" alt="" class="news-modal-header-img" style="display: none;">
                <div id="modal-fallback-img" style="display: none; width: 100%; height: 260px; background: linear-gradient(135deg, rgba(139, 92, 246, 0.4) 0%, rgba(217, 70, 239, 0.3) 100%); justify-content: center; align-items: center;">
                    <span style="font-size: 3rem; opacity: 0.6;">⚡</span>
                </div>
            </div>

            <div class="news-modal-body">
                <!-- 🔊 AI Voice Player Integrato (WAHU!) -->
                <div class="tts-player-container">
                    <button class="tts-play-btn" id="tts-play-btn" onclick="toggleTTS()" aria-label="Ascolta l'articolo ad alta voce">
                        <span id="tts-icon">🔊</span> Ascolta l'Articolo
                    </button>
                    <div class="sound-wave" id="sound-wave" style="display: none;">
                        <span class="stroke"></span>
                        <span class="stroke"></span>
                        <span class="stroke"></span>
                        <span class="stroke"></span>
                        <span class="stroke"></span>
                    </div>
                </div>

                <div class="news-modal-meta">
                    <span id="modal-tag" class="news-card-tag">Tech Insight</span>
                    <span id="modal-date" class="news-modal-date"></span>
                </div>
                
                <h1 id="modal-title" class="news-modal-title"></h1>
                
                <div id="modal-content" class="news-modal-text"></div>
                
                <div id="modal-source-wrapper" style="margin-top: 40px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.06); display: none;">
                    <a id="modal-source-link" href="" target="_blank" class="btn" style="padding: 10px 20px; font-size: 0.9rem;">
                        <span>Visita la Fonte Originale ↗</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="glass footer-pill" style="display: flex; flex-direction: column; align-items: center; gap: 10px;">
                <p>&copy; 2026 Davide Fiore - CyberSecurity & Software Dev</p>
                <div class="footer-links" style="font-size: 0.85rem;">
                    <a href="privacy_policy.html" style="color: var(--text-sec); margin: 0 10px; text-decoration: none;">Privacy Policy</a>
                    <a href="cookie_policy.html" style="color: var(--text-sec); margin: 0 10px; text-decoration: none;">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="js/main.js?v=25"></script>
    <script src="https://cdn.jsdelivr.net/gh/studio-freight/lenis@1.0.29/bundled/lenis.min.js"></script>
    <script src="js/premium-effects.js?v=25"></script>

    <!-- JavaScript Interactive Logic -->
    <script>
        // 🟢 1. FILTRI CATEGORIA CON TRANSIZIONE FLUIDA
        function filterCategory(category) {
            // Riconfigura il bottone attivo
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.currentTarget.classList.add('active');

            const cards = document.querySelectorAll('.news-card');
            
            cards.forEach(card => {
                const cardCat = card.getAttribute('data-category');
                
                if (category === 'all' || cardCat === category) {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(15px) scale(0.96)';
                    card.style.display = 'flex';
                    
                    // Trigger reflow per avviare transizione
                    setTimeout(() => {
                        card.style.transition = 'all 0.5s var(--ease-apple)';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0) scale(1)';
                    }, 50);
                } else {
                    card.style.transition = 'all 0.35s var(--ease-apple)';
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(15px) scale(0.96)';
                    
                    setTimeout(() => {
                        card.style.display = 'none';
                    }, 350);
                }
            });
        }

        // 🟢 2. LOGICA MODALE DETTAGLIO
        function openNewsModal(article, category) {
            const modal = document.getElementById('news-detail-modal');
            const titleEl = document.getElementById('modal-title');
            const dateEl = document.getElementById('modal-date');
            const tagEl = document.getElementById('modal-tag');
            const contentEl = document.getElementById('modal-content');
            const imgEl = document.getElementById('modal-img');
            const fallbackEl = document.getElementById('modal-fallback-img');
            const sourceWrapper = document.getElementById('modal-source-wrapper');
            const sourceLink = document.getElementById('modal-source-link');

            // Reset progress bar e player audio
            document.getElementById('modal-progress').style.width = '0%';
            resetTTSPlayer();

            // Popola dati
            titleEl.textContent = article.title;
            dateEl.textContent = formatDate(article.created_at);
            tagEl.textContent = category;
            contentEl.innerHTML = article.content || '<p>Nessun contenuto disponibile per questo articolo.</p>';

            // Gestione Immagine
            if (article.image_url && article.image_url.trim() !== '') {
                imgEl.src = article.image_url;
                imgEl.alt = article.title;
                imgEl.style.display = 'block';
                fallbackEl.style.display = 'none';
            } else {
                imgEl.style.display = 'none';
                fallbackEl.style.display = 'flex';
            }

            // Gestione Fonte Esterna
            if (article.external_url && article.external_url.trim() !== '') {
                sourceLink.href = article.external_url;
                sourceWrapper.style.display = 'block';
            } else {
                sourceWrapper.style.display = 'none';
            }

            // Mostra Modal
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            
            // Focus sul pulsante di chiusura per l'accessibilità (A11y)
            setTimeout(() => {
                modal.querySelector('.news-modal-close-btn').focus();
            }, 100);
        }

        function closeNewsModal() {
            const modal = document.getElementById('news-detail-modal');
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            
            // Interrompe la lettura vocale quando si chiude la modale
            window.speechSynthesis.cancel();
            resetTTSPlayer();
        }

        // Formattazione data
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return dateStr;
            
            const months = [
                'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
                'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'
            ];
            
            return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
        }

        // 🟢 3. BARRA DI PROGRESSOLETTURA IN TEMPO REALE
        const modalContainer = document.querySelector('.news-modal-container');
        modalContainer.addEventListener('scroll', () => {
            const winScroll = modalContainer.scrollTop;
            const height = modalContainer.scrollHeight - modalContainer.clientHeight;
            const scrolled = height > 0 ? (winScroll / height) * 100 : 0;
            document.getElementById('modal-progress').style.width = scrolled + '%';
        });

        // 🟢 4. AI VOICE "READ ALOUD" PLAYER (Web Speech API)
        let isSpeaking = false;
        let currentUtterance = null;

        function toggleTTS() {
            const playBtn = document.getElementById('tts-play-btn');
            const iconEl = document.getElementById('tts-icon');
            const waveEl = document.getElementById('sound-wave');
            
            if (isSpeaking) {
                // Metti in pausa / cancella
                window.speechSynthesis.cancel();
                isSpeaking = false;
                iconEl.textContent = '🔊';
                playBtn.innerHTML = '<span id="tts-icon">🔊</span> Ascolta l\'Articolo';
                waveEl.style.display = 'none';
            } else {
                // Ferma eventuali letture residue
                window.speechSynthesis.cancel();
                
                // Estrai titolo e testo pulito
                const titleText = document.getElementById('modal-title').innerText;
                const bodyHTML = document.getElementById('modal-content').innerHTML;
                
                // Pulizia dei tag HTML per far leggere solo testo pulito
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = bodyHTML;
                const bodyText = tempDiv.innerText || tempDiv.textContent;
                
                const fullText = titleText + ". " + bodyText;
                
                currentUtterance = new SpeechSynthesisUtterance(fullText);
                currentUtterance.lang = 'it-IT';
                
                // Configura voce italiana se disponibile
                const voices = window.speechSynthesis.getVoices();
                const italianVoice = voices.find(voice => voice.lang.startsWith('it'));
                if (italianVoice) {
                    currentUtterance.voice = italianVoice;
                }

                currentUtterance.onend = function() {
                    resetTTSPlayer();
                };
                
                currentUtterance.onerror = function() {
                    resetTTSPlayer();
                };

                window.speechSynthesis.speak(currentUtterance);
                isSpeaking = true;
                iconEl.textContent = '⏸';
                playBtn.innerHTML = '<span id="tts-icon">⏸</span> Pausa Lettura';
                waveEl.style.display = 'flex';
            }
        }

        function resetTTSPlayer() {
            isSpeaking = false;
            const playBtn = document.getElementById('tts-play-btn');
            if (playBtn) {
                playBtn.innerHTML = '<span id="tts-icon">🔊</span> Ascolta l\'Articolo';
            }
            const waveEl = document.getElementById('sound-wave');
            if (waveEl) {
                waveEl.style.display = 'none';
            }
        }

        // Caricamento asincrono voci per Web Speech API
        window.speechSynthesis.onvoiceschanged = function() {
            // Aggiorna le voci disponibili del browser
        };

        // Event Listeners di chiusura modale
        document.getElementById('modal-overlay').addEventListener('click', closeNewsModal);
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeNewsModal();
            }
        });

        // Supporto accessibilità tastiera per le cards
        document.querySelectorAll('.news-card').forEach(card => {
            card.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    this.click();
                }
            });
        });
    </script>
</body>

</html>
