import React from 'react';

export default function Navbar() {
  return (
    <>
      <a href="/" className="brand-link">
        <img src="/new_IMage.png" alt="RMS Logo" className="brand" />
      </a>
      <nav className="nav-links">
        <a className="nav-link" href="#features">Features</a>
        <a className="nav-link" href="#workflow">Workflow</a>
        <a className="nav-link" href="#contact">Contact</a>
      </nav>
      <div className="cta-group">
        <a className="button-secondary" href="#contact">Request Demo</a>
        <a className="button" href="/login">Login</a>
      </div>
    </>
  );
}
