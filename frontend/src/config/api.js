const DEFAULT_API_BASE_URL = 'http://localhost/temp/RMS/backend';

export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || DEFAULT_API_BASE_URL;

export function apiUrl(path) {
  const normalizedPath = path.startsWith('/') ? path : `/${path}`;
  return `${API_BASE_URL}/index.php${normalizedPath}`;
}

export const getUsers = async () => {
  const response = await fetch(apiUrl('/users/manage.php?action=list'), {
    credentials: 'include',
  });
  return response.json();
};

export const createUser = async (userData) => {
  const response = await fetch(apiUrl('/users/manage.php?action=create'), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(userData),
  });
  return response.json();
};

export const getFacilities = async () => {
  const response = await fetch(apiUrl('/facilities/manage_facilities.php'), {
    credentials: 'include',
  });
  return response.json();
};

// Get departments (ikiwa haipo, tutaunda backend yake baadaye)
export const getDepartments = async () => {
  // Kwa sasa, tumia data hardcoded au unda API ya departments
  // Nitaandika backend ya departments baadaye
  return { departments: [
    { id: 1, name: 'Internal Medicine' },
    { id: 2, name: 'Surgery' },
    { id: 3, name: 'Pediatrics' },
    { id: 4, name: 'Obstetrics & Gynecology' },
  ]};
};
