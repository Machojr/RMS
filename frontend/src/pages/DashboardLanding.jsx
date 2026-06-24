import React, { useEffect, useState } from 'react';
import { apiUrl } from '../config/api.js';

const modulesAvailable = {
  co: [
    { title: 'My Referrals', desc: 'View and manage your referred patients', path: '/dashboard?tab=referrals', iconClass: 'fa-solid fa-file-medical' },
    { title: 'Patients', desc: 'Review patient profiles linked to your referrals', path: '/dashboard?tab=patients', iconClass: 'fa-solid fa-user-injured' },
    { title: 'Facilities', desc: 'Browse all facilities in the network', path: '/dashboard?tab=facilities', iconClass: 'fa-solid fa-hospital' },
    { title: 'Feedback', desc: 'Clinical outcomes from receiving centers', path: '/dashboard?tab=feedback', iconClass: 'fa-solid fa-notes-medical' },
  ],
  admin: [
    { title: 'Referrals', desc: 'Manage all facility referrals', path: '/dashboard?tab=referrals', iconClass: 'fa-solid fa-file-medical' },
    { title: 'Patients', desc: 'Review patient records connected to your facility', path: '/dashboard?tab=patients', iconClass: 'fa-solid fa-user-injured' },
    { title: 'Facilities', desc: 'Facility network overview', path: '/dashboard?tab=facilities', iconClass: 'fa-solid fa-hospital' },
    { title: 'Feedback', desc: 'Monitor clinical feedback', path: '/dashboard?tab=feedback', iconClass: 'fa-solid fa-notes-medical' },
    { title: 'Notifications', desc: 'Communication audit logs', path: '/dashboard?tab=notifications', iconClass: 'fa-solid fa-bell' },
  ],
  moh: [
    { title: 'Referrals', desc: 'National referral oversight', path: '/dashboard?tab=referrals', iconClass: 'fa-solid fa-file-medical' },
    { title: 'Patients', desc: 'View patient records across referral activity', path: '/dashboard?tab=patients', iconClass: 'fa-solid fa-user-injured' },
    { title: 'Facilities', desc: 'All-nation facility data', path: '/dashboard?tab=facilities', iconClass: 'fa-solid fa-hospital' },
    { title: 'Feedback', desc: 'Aggregate clinical outcomes', path: '/dashboard?tab=feedback', iconClass: 'fa-solid fa-notes-medical' },
    { title: 'Notifications', desc: 'System-wide communications', path: '/dashboard?tab=notifications', iconClass: 'fa-solid fa-bell' },
  ],
};

const roleDescriptions = {
  co: 'As a CO, you submit patient referrals, monitor referral progress, and review clinical feedback from receiving facilities.',
  admin: 'As a Hospital Admin, you manage facility referrals, accept or reroute transfers, and oversee feedback within your facility.',
  moh: 'As a Ministry of Health official, you monitor referrals at the national level and track facility performance across regions.',
};

const roleLabels = {
  co: 'CO',
  admin: 'Hospital Admin',
  moh: 'MOH Official',
};

export default function DashboardLanding() {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function fetchUser() {
      try {
        const response = await fetch(apiUrl('/dashboard/summary.php'), {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setUser(data.summary.user);
        } else {
          window.location.href = '/login';
        }
      } catch (err) {
        window.location.href = '/login';
      } finally {
        setLoading(false);
      }
    }

    fetchUser();
  }, []);

  if (loading) {
    return <main className="container"><p className="dashboard-message">Loading...</p></main>;
  }

  const modules = modulesAvailable[user?.role] || [];

  return (
    <main className="container dashboard-page">
      <section className="section dashboard-section">
        <div className="landing-header">
          <div className="landing-title">
            <span>Welcome back, {user?.first_name}!</span>
            <h1>Your RMS Portal</h1>
            <p className="role-badge">Role: <strong>{roleLabels[user?.role] || user?.role?.toUpperCase()}</strong></p>
            <p className="role-description">{roleDescriptions[user?.role] || 'Choose your dashboard modules to begin.'}</p>
          </div>
          <a href="/login" className="button-secondary" onClick={(e) => {
            e.preventDefault();
            fetch(apiUrl('/auth/logout.php'), {
              method: 'POST',
              credentials: 'include',
            }).then(() => {
              window.location.href = '/';
            });
          }}>Sign Out</a>
        </div>

        <div className="modules-grid">
          {modules.map((module) => (
            <a key={module.path} href={module.path} className="module-card">
              <span className="module-icon" aria-hidden="true">
                <i className={module.iconClass}></i>
              </span>
              <h3>{module.title}</h3>
              <p>{module.desc}</p>
              <span className="arrow" aria-hidden="true">
                <i className="fa-solid fa-arrow-right"></i>
              </span>
            </a>
          ))}
        </div>
      </section>
    </main>
  );
}
