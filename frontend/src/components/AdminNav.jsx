import React from 'react';

const tabs = [
  { id: 'overview', label: 'Overview' },
  { id: 'users', label: 'Users' },
  { id: 'facilities', label: 'Facilities' },
];

export default function AdminNav({ activeTab, onChange }) {
  return (
    <nav className="dashboard-nav">
      {tabs.map((tab) => (
        <a
          key={tab.id}
          href={`/admin?tab=${tab.id}`}
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