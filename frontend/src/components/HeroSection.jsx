import React from 'react';

export default function HeroSection() {
  return (
    <div className="hero-copy">
      <span style={{ color: '#22d3ee', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.22em' }}>
        Referral Management
      </span>
      <h1>Modernize Tanzania’s referral network with a secure digital platform.</h1>
      <p>
        RMS is a professional referral system built for public healthcare facilities. It brings one unified workflow for COs,
        facility administrators, and Ministry of Health officials.
      </p>
      <div className="hero-actions">
        <a className="button" href="#features">Explore Features</a>
        <a className="button-secondary" href="#workflow">See Workflow</a>
      </div>

      <div className="stats">
        <div className="stat-card">
          <div className="stat-number">99.9%</div>
          <p className="stat-label">Referral delivery reliability</p>
        </div>
        <div className="stat-card">
          <div className="stat-number">4.8/5</div>
          <p className="stat-label">User satisfaction score</p>
        </div>
        <div className="stat-card">
          <div className="stat-number">5</div>
          <p className="stat-label">Health system tiers covered</p>
        </div>
      </div>
    </div>
  );
}
