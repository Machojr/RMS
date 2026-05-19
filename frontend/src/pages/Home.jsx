import React from 'react';
import Navbar from '../components/Navbar.jsx';
import HeroSection from '../components/HeroSection.jsx';
import FeatureCard from '../components/FeatureCard.jsx';

const features = [
  {
    title: 'Smart Referral Workflow',
    description: 'Create, send, and track referrals through every tier of the health system with automated updates.',
  },
  {
    title: 'Role-Based Dashboards',
    description: 'COs, facility admins, and MOH officials each see the data and actions that matter to them.',
  },
  {
    title: 'Real-Time Notification Engine',
    description: 'Email and SMS alerts keep referring and receiving teams synchronized at every referral stage.',
  },
  {
    title: 'Clinical Feedback Loop',
    description: 'Receive outcome reports and discharge summaries directly from the receiving facility.',
  },
];

const howItWorks = [
  'Submit a referral from any facility in the network.',
  'Receiving facility reviews, accepts, and updates the referral status.',
  'Patient care progress is tracked until completion.',
  'Clinical feedback is sent back to the referring CO automatically.',
];

const roles = [
  {
    title: 'CO',
    description: 'Submit patient referrals, follow referral status, and receive clinical outcomes from receiving facilities.',
  },
  {
    title: 'Hospital Admin',
    description: 'Oversee facility referrals, accept transfers, and manage clinical feedback for your facility.',
  },
  {
    title: 'MOH Official',
    description: 'Monitor national referral performance, facility coverage, and health system outcomes.',
  },
];

export default function Home() {
  return (
    <div>
      <header className="header">
        <div className="container navbar">
          <Navbar />
        </div>
      </header>

      <main className="container">
        <section id="hero" className="section hero">
          <HeroSection />
        </section>

        <section id="features" className="section">
          <div className="section-title">
            <span>Platform Highlights</span>
            <h2>Everything your referral network needs, in one modern system.</h2>
          </div>

          <div className="feature-grid" style={{ marginTop: '2rem' }}>
            {features.map((feature) => (
              <FeatureCard key={feature.title} title={feature.title} description={feature.description} />
            ))}
          </div>
        </section>

        <section id="workflow" className="section">
          <div className="section-title">
            <span>How RMS Works</span>
            <h2>Simple, transparent referral flow for every stakeholder.</h2>
          </div>

          <div className="cards" style={{ marginTop: '2rem' }}>
            {howItWorks.map((item, index) => (
              <div key={item} className="card">
                <div className="stat-number">0{index + 1}</div>
                <h3>{item}</h3>
              </div>
            ))}
          </div>
        </section>

        <section id="roles" className="section">
          <div className="section-title">
            <span>Defined Roles</span>
            <h2>Each user has a clear, purpose-built role in the referral flow.</h2>
          </div>

          <div className="role-grid" style={{ marginTop: '2rem' }}>
            {roles.map((role) => (
              <div key={role.title} className="card role-card">
                <h3>{role.title}</h3>
                <p>{role.description}</p>
              </div>
            ))}
          </div>
        </section>

        <section id="contact" className="section">
          <div className="section-title">
            <span>Get Started</span>
            <h2>Bring clarity to your referral process with RMS.</h2>
          </div>

          <div className="card" style={{ marginTop: '2rem', textAlign: 'center' }}>
            <h3>Ready to strengthen patient referrals across Tanzania?</h3>
            <p>
              The RMS homepage is your first step toward digital referral tracking, role-based workflows, and responsive communication.
            </p>
            <a className="button" href="/login" style={{ marginTop: '1.75rem', display: 'inline-block' }}>
              Continue to Login
            </a>
          </div>
        </section>
      </main>

      <footer className="footer">
        <div className="container">
          <p>Referral Management System · Designed for Tanzania’s public healthcare network · 2026</p>
        </div>
      </footer>
    </div>
  );
}
