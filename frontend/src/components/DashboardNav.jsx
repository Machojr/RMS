import React from 'react';

const tabs = [
  { id: 'overview', label: 'Overview' },
  { id: 'referrals', label: 'Referrals' },
  { id: 'facilities', label: 'Facilities' },
  { id: 'feedback', label: 'Feedback' },
  { id: 'notifications', label: 'Notifications' },
];

export default function DashboardNav({ activeTab, onChange }) {
  return (
    <nav className="dashboard-nav">
      {tabs.map((tab) => (
        <a
          key={tab.id}
          href={`/dashboard?tab=${tab.id}`}
          className={activeTab === tab.id ? 'active' : ''}
          onClick={(event) => {
            event.preventDefault();
            onChange(tab.id);
          }}
        >
          {tab.label}
        </a>
      ))}
    </nav>
  );
}
