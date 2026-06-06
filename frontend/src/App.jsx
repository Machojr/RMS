import React from 'react';
import Home from './pages/Home.jsx';
import Login from './pages/Login.jsx';
import DashboardLanding from './pages/DashboardLanding.jsx';
import Dashboard from './pages/Dashboard.jsx';

function App() {
  const path = window.location.pathname;

  if (path === '/login') {
    return <Login />;
  }

  if (path === '/dashboard-landing') {
    return <DashboardLanding />;
  }

  if (path === '/dashboard') {
    return <Dashboard />;
  }

  return <Home />;
}

export default App;
