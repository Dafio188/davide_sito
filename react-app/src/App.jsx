import React from 'react';
import DataGlobe3D from './components/DataGlobe3D';
import Chatbot from './components/Chatbot';
import './App.css';

function App() {
  return (
    <div className="app">
      {/* Sfondo Animato */}
      <div className="background-blobs">
        <div className="blob blob-1"></div>
        <div className="blob blob-2"></div>
        <div className="blob blob-3"></div>
      </div>

      {/* Navigazione */}
      <nav className="glass">
        <div className="logo">Davide Fiore</div>
        <div className="nav-links">
          <a href="#about">Chi Sono</a>
          <a href="#services">Servizi</a>
          <a href="#contact">Contatti</a>
        </div>
      </nav>

      {/* Hero Section */}
      <section className="container hero" id="about">
        <img src="/avatar_new.png" alt="Davide Fiore Avatar" className="hero-avatar" />
        <span className="tag">Security • Development • AI</span>
        <h1>Sviluppo Solido<br />Intelligenza Sicura</h1>
        <p>Unisco l'ingegneria del software e la CyberSecurity per creare soluzioni AI e Web App che trasformano il business, senza rischi.</p>
        <div className="cta-group">
          <a href="#contact" className="btn">Prenota una Call Conoscitiva</a>
        </div>
      </section>

      {/* DataGlobe 3D Section */}
      <section className="container">
        <DataGlobe3D />
      </section>

      {/* Services Section */}
      <section className="container" id="services">
        <h2 className="section-title">Ecosistema Digitale</h2>
        <p className="section-subtitle">Dalle basi del codice all'addestramento AI personalizzato</p>

        <div className="bento-grid">
          <div className="card glass span-1">
            <div>
              <div className="icon-box">💻</div>
              <h3>Sviluppo App & Web</h3>
            </div>
            <p>Web App reattive e Applicazioni Android native. Codice pulito, architetture scalabili e focus maniacale sulla User Experience</p>
          </div>

          <div className="card glass span-2">
            <div>
              <div className="icon-box">📊</div>
              <h3>CRM & Gestionali PMI</h3>
            </div>
            <p>Abbandona i fogli Excel per sistemi centralizzati, sicuri e accessibili ovunque. Dashboard personalizzate per il controllo totale del tuo business</p>
          </div>

          <div className="card glass card-dark span-2">
            <div>
              <div className="icon-box">🧠</div>
              <h3>AI & Sistemi RAG</h3>
            </div>
            <p>La potenza dell'AI specifica per i tuoi dati. Creo <strong>Chatbot personalizzati</strong> con tecnologia RAG che rispondono basandosi sui tuoi documenti aziendali</p>
          </div>

          <div className="card glass span-1">
            <div>
              <div className="icon-box">🎓</div>
              <h3>Training AI & Security</h3>
            </div>
            <p>Formazione pratica per professionisti: impara a scrivere prompt efficaci, integrare l'AI e proteggere i tuoi asset digitali</p>
          </div>
        </div>
      </section>

      {/* Lab Section */}
      <section className="container" id="lab">
        <h2 className="section-title">Personal Lab</h2>
        <p className="section-subtitle">Oltre il codice aziendale: progetti indipendenti, astronomia e game development</p>

        <div className="lab-grid">
          <div className="glass lab-card">
            <div className="lab-image-container">
              <img src="/astroguida_preview.webp" alt="AstroGuida App" className="lab-image" />
            </div>
            <div className="lab-content">
              <div className="lab-header">
                <h3>AstroGuida</h3>
                <span className="tag-small">Android App</span>
              </div>
              <p>L'app di riferimento per l'astronomia in Puglia. Mappe stellari, eventi e community in tasca</p>
              <div className="lab-actions">
                <a href="https://play.google.com/store/apps/details?id=com.astroguida.app_gemini_astroguida" target="_blank" rel="noreferrer" className="btn-text">📲 Play Store</a>
                <a href="https://www.astroguida.com" target="_blank" rel="noreferrer" className="btn-text">🌍 Sito Web</a>
              </div>
            </div>
          </div>

          <div className="glass lab-card">
            <div className="lab-image-container">
              <img src="/ruota_preview.jpg" alt="Ruota della Conoscenza" className="lab-image" />
            </div>
            <div className="lab-content">
              <div className="lab-header">
                <h3>Ruota della Conoscenza</h3>
                <span className="tag-small">Trivia Game</span>
              </div>
              <p>Un gioco educativo con migliaia di domande. Sfida la tua conoscenza</p>
              <div className="lab-actions">
                <span className="btn-text" style={{ opacity: 0.5 }}>Coming to Stores</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Contact Section */}
      <section className="container" id="contact" style={{ textAlign: 'center', paddingBottom: '80px' }}>
        <h2 className="section-title">Parliamo del tuo progetto</h2>
        <p className="section-subtitle">Hai un'idea o una sfida tecnica? Costruiamola insieme</p>

        <div className="glass contact-card-form">
          <div className="contact-header">
            <p><strong>Davide Fiore</strong></p>
            <p style={{ color: 'var(--accent)' }}>Lascia la tua idea, ti ricontatterò</p>
          </div>

          <form name="idea-contact" method="POST" data-netlify="true">
            <input type="email" name="email" placeholder="La tua email (per risponderti)" required />
            <textarea name="idea" placeholder="Ciao Davide, ho un'idea per un progetto..." required></textarea>
            <button type="submit" className="btn btn-full">Richiedi Consulenza Gratuita</button>
          </form>

          <div style={{ marginTop: '20px', fontSize: '0.9rem', color: 'var(--text-sec)' }}>
            Preferisci usare la tua mail? <a href="mailto:info@davidefiore.com" style={{ color: 'var(--accent)', textDecoration: 'none', fontWeight: 600 }}>Scrivimi direttamente</a>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer>
        <div className="container">
          <p className="glass footer-pill">
            © 2025 Davide Fiore - CyberSecurity & Software Dev
          </p>
        </div>
      </footer>

      {/* Chatbot */}
      <Chatbot />
    </div>
  );
}

export default App;
