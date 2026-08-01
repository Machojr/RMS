import React from 'react';
import Home from './pages/Home.jsx';
import Login from './pages/Login.jsx';
import DashboardLanding from './pages/DashboardLanding.jsx';
import Dashboard from './pages/Dashboard.jsx';
// Import ya Admin Dashboard (Umeunda hii)
import AdminDashboard from './pages/AdminDashboard.jsx';

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

  // ROUTE MPYA YA ADMIN (Imeongezwa hapa kabla ya Home)
  if (path === '/admin' || path === '/admin/dashboard') {
    return <AdminDashboard />;
  }

  return <Home />;
}

export default App;