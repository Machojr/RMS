import React, { useEffect, useState } from 'react';
import DashboardNav from '../components/DashboardNav.jsx';

export default function Facilities() {
  const [facilities, setFacilities] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    async function fetchFacilities() {
      try {
        const response = await fetch('http://localhost/rms/backend/facilities/manage_facilities.php', {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setFacilities(data.facilities);
        } else {
          setError(data.error || 'Unable to load facilities.');
        }
      } catch (err) {
        setError('Unable to reach facilities service.');
      } finally {
        setLoading(false);
      }
    }

    fetchFacilities();
  }, []);

  return (
    <main className="container dashboard-page">
      <section className="section dashboard-section">
        <div className="nav-top">
          <div>
            <DashboardNav />
          </div>
          <a href="/" className="button-secondary" onClick={(e) => {
            e.preventDefault();
            fetch('http://localhost/rms/backend/auth/logout.php', {
              method: 'POST',
              credentials: 'include',
            }).then(() => {
              window.location.href = '/';
            });
          }}>Sign Out</a>
        </div>
        <div className="section-title">
          <span>Facilities Network</span>
          <h2>Review facility capacity, tier, and regional coverage.</h2>
        </div>

        {loading ? (
          <p className="dashboard-message">Loading facilities...</p>
        ) : error ? (
          <p className="dashboard-message dashboard-error">{error}</p>
        ) : (
          <div className="grid-card facility-grid">
            {facilities.length === 0 ? (
              <p>No facilities available yet.</p>
            ) : (
              facilities.map((facility) => (
                <div key={facility.id} className="facility-card">
                  <div className="facility-header">
                    <h3>{facility.name}</h3>
                    <span className="tier-tag">{facility.tier}</span>
                  </div>
                  <p>{facility.region}, {facility.district}</p>
                  <p>{facility.address}</p>
                  <p>{facility.phone} · {facility.email}</p>
                  <p><strong>Capacity:</strong> {facility.capacity}</p>
                </div>
              ))
            )}
          </div>
        )}
      </section>
    </main>
  );
}
