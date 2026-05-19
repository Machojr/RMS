import React, { useEffect, useState } from 'react';

const slides = ['/Image1.jpg', '/image2.jpg'];

export default function HeroSection() {
  const [activeSlide, setActiveSlide] = useState(0);

  useEffect(() => {
    const timer = setInterval(() => {
      setActiveSlide((current) => (current + 1) % slides.length);
    }, 5000);
    return () => clearInterval(timer);
  }, []);

  return (
    <>
      <div className="hero-slideshow">
        {slides.map((slide, index) => (
          <div key={slide} className={`hero-slide ${index === activeSlide ? 'active' : ''}`}>
            <img src={slide} alt={`Slide ${index + 1}`} />
          </div>
        ))}
        <div className="hero-overlay" />
      </div>

      <div className="hero-copy">
        <span>Referral Management</span>
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
    </>
  );
}
