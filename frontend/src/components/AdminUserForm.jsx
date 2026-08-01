// frontend/src/components/AdminUserForm.jsx
import React, { useState, useEffect } from 'react';
import { getFacilities, getDepartments } from '../config/api';

const AdminUserForm = ({ onSubmit, onCancel }) => {
  const [formData, setFormData] = useState({
    email: '',
    password: '',
    first_name: '',
    last_name: '',
    phone: '',
    role: 'co',
    facility_id: '',
    department_id: '',
    license_number: '',
  });
  const [facilities, setFacilities] = useState([]);
  const [departments, setDepartments] = useState([]);

  useEffect(() => {
    // Fetch facilities and departments for dropdowns
    const fetchData = async () => {
      try {
        const facilitiesData = await getFacilities();
        setFacilities(facilitiesData.facilities || []);
        const deptData = await getDepartments();
        setDepartments(deptData.departments || []);
      } catch (error) {
        console.error('Error fetching dropdown data:', error);
      }
    };
    fetchData();
  }, []);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData({ ...formData, [name]: value });
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    // Basic validation
    if (!formData.email || !formData.password || !formData.first_name || !formData.last_name) {
      alert('Tafadhali jaza sehemu zote muhimu (Email, Password, First Name, Last Name)');
      return;
    }
    if (formData.role === 'co' && (!formData.department_id || !formData.license_number)) {
      alert('Kwa CO, tafadhali jaza Idara na Namba ya Leseni');
      return;
    }
    onSubmit(formData);
  };

  return (
    <form onSubmit={handleSubmit}>
      <div className="row">
        <div className="col-md-6 mb-3">
          <label className="form-label">Jina la Kwanza *</label>
          <input type="text" className="form-control" name="first_name" value={formData.first_name} onChange={handleChange} required />
        </div>
        <div className="col-md-6 mb-3">
          <label className="form-label">Jina la Ukoo *</label>
          <input type="text" className="form-control" name="last_name" value={formData.last_name} onChange={handleChange} required />
        </div>
      </div>
      <div className="row">
        <div className="col-md-6 mb-3">
          <label className="form-label">Barua-pepe (Email) *</label>
          <input type="email" className="form-control" name="email" value={formData.email} onChange={handleChange} required />
        </div>
        <div className="col-md-6 mb-3">
          <label className="form-label">Nenosiri *</label>
          <input type="password" className="form-control" name="password" value={formData.password} onChange={handleChange} required />
        </div>
      </div>
      <div className="row">
        <div className="col-md-6 mb-3">
          <label className="form-label">Namba ya Simu</label>
          <input type="text" className="form-control" name="phone" value={formData.phone} onChange={handleChange} />
        </div>
        <div className="col-md-6 mb-3">
          <label className="form-label">Role *</label>
          <select className="form-select" name="role" value={formData.role} onChange={handleChange} required>
            <option value="co">CO / Doctor</option>
            <option value="receptionist">Receptionist</option>
            <option value="admin">Admin</option>
          </select>
        </div>
      </div>
      <div className="mb-3">
        <label className="form-label">Hospitali (Facility)</label>
        <select className="form-select" name="facility_id" value={formData.facility_id} onChange={handleChange}>
          <option value="">-- Chagua Hospitali --</option>
          {facilities.map((fac) => (
            <option key={fac.id} value={fac.id}>{fac.name}</option>
          ))}
        </select>
      </div>

      {/* Fields only for CO */}
      {formData.role === 'co' && (
        <div className="row">
          <div className="col-md-6 mb-3">
            <label className="form-label">Idara (Department) *</label>
            <select className="form-select" name="department_id" value={formData.department_id} onChange={handleChange} required>
              <option value="">-- Chagua Idara --</option>
              {departments.map((dept) => (
                <option key={dept.id} value={dept.id}>{dept.name}</option>
              ))}
            </select>
          </div>
          <div className="col-md-6 mb-3">
            <label className="form-label">Namba ya Leseni (License) *</label>
            <input type="text" className="form-control" name="license_number" value={formData.license_number} onChange={handleChange} required />
          </div>
        </div>
      )}

      <div className="d-flex justify-content-end">
        <button type="button" className="btn btn-secondary me-2" onClick={onCancel}>Ghairi</button>
        <button type="submit" className="btn btn-primary">Sajili</button>
      </div>
    </form>
  );
};

export default AdminUserForm;
