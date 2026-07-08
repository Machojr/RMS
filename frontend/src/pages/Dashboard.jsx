import React, { useEffect, useState } from 'react';
import DashboardNav from '../components/DashboardNav.jsx';
import { apiUrl } from '../config/api.js';

const sectionMeta = {
  overview: {
    title: 'Central Dashboard',
    subtitle: 'Access referrals, facilities, feedback and alerts from one page.',
  },
  referrals: {
    title: 'Referral Management',
    subtitle: 'Track every referral from submission to closure.',
  },
  patients: {
    title: 'Patient Management',
    subtitle: 'View patient profiles linked to referral activity.',
  },
  facilities: {
    title: 'Facilities Network',
    subtitle: 'Review facility capacity, tier, and regional coverage.',
  },
  feedback: {
    title: 'Clinical Feedback',
    subtitle: 'View outcome notes and discharge summaries from receiving facilities.',
  },
  notifications: {
    title: 'Notifications',
    subtitle: 'Audit your referral communication and recent alerts.',
  },
};

const referralExtraSections = [
  {
    title: 'Referral Identification',
    fields: [
      { name: 'patient_number', label: 'Patient Number', type: 'text' },
      { name: 'age_years', label: 'Age', type: 'text' },
      { name: 'region', label: 'Region', type: 'text' },
      { name: 'district', label: 'District', type: 'text' },
      { name: 'transfer_date', label: 'Transfer Date', type: 'date' },
      { name: 'referral_number', label: 'Referral Number', type: 'text' },
    ],
  },
  {
    title: 'Clinical Assessment',
    fields: [
      { name: 'diagnosis', label: 'Diagnosis', type: 'textarea' },
      { name: 'temperature', label: 'Temperature', type: 'text' },
      { name: 'heart_rate', label: 'Heart Rate', type: 'text' },
      { name: 'respiratory_rate', label: 'Respiratory Rate', type: 'text' },
      { name: 'blood_pressure', label: 'Blood Pressure', type: 'text' },
      { name: 'mental_status', label: 'Mental Status', type: 'text' },
      { name: 'treatment_before_transfer', label: 'Treatment Rendered Prior to Transfer', type: 'textarea' },
      { name: 'reason_for_transfer', label: 'Reason for Transfer', type: 'textarea' },
    ],
  },
];

const referralDetailGroups = [
  {
    title: 'Referral Identification',
    fields: [
      ['Patient Number', 'patient_number'],
      ['Age', 'age_years'],
      ['Region', 'region'],
      ['District', 'district'],
      ['Transfer Date', 'transfer_date'],
      ['Referral Number', 'referral_number'],
    ],
  },
  {
    title: 'Clinical Assessment',
    fields: [
      ['Diagnosis', 'diagnosis'],
      ['Temperature', 'temperature'],
      ['Heart Rate', 'heart_rate'],
      ['Respiratory Rate', 'respiratory_rate'],
      ['Blood Pressure', 'blood_pressure'],
      ['Mental Status', 'mental_status'],
      ['Treatment Before Transfer', 'treatment_before_transfer'],
      ['Reason for Transfer', 'reason_for_transfer'],
    ],
  },
  {
    title: 'Referring Doctor',
    fields: [
      ['Doctor Name', 'doctor_name'],
      ['Doctor Phone Number', 'doctor_phone'],
    ],
  },
];

export default function Dashboard() {
  const [activeTab, setActiveTab] = useState('overview');
  const [contentVisible, setContentVisible] = useState(true);
  const [summary, setSummary] = useState(null);
  const [loadingState, setLoadingState] = useState({
    overview: false,
    referrals: false,
    patients: false,
    facilities: false,
    feedback: false,
    notifications: false,
  });
  const [sectionErrors, setSectionErrors] = useState({
    overview: '',
    referrals: '',
    patients: '',
    facilities: '',
    feedback: '',
    notifications: '',
  });
  const [referrals, setReferrals] = useState([]);
  const [patients, setPatients] = useState([]);
  const [facilities, setFacilities] = useState([]);
  const [feedbackItems, setFeedbackItems] = useState([]);
  const [notifications, setNotifications] = useState([]);
  const [notificationForm, setNotificationForm] = useState({
    referral_id: '',
    department_id: '',
    recipient_doctor_id: '',
    notification_type: 'email',
    note: '',
  });
  const [notificationMessage, setNotificationMessage] = useState('');
  const [sendingNotification, setSendingNotification] = useState(false);
  const [notificationError, setNotificationError] = useState('');
  const [notificationDepartments, setNotificationDepartments] = useState([]);
  const [notificationDoctors, setNotificationDoctors] = useState([]);
  const [feedbackForm, setFeedbackForm] = useState({
    referral_id: '',
    department: '',
    referral_serial_no: '',
    referral_diagnosis: '',
    confirmed_diagnosis: '',
    comments: '',
    clinical_outcome: '',
    treatment_given: '',
    discharge_summary: '',
    follow_up_instructions: '',
  });
  const [feedbackMessage, setFeedbackMessage] = useState('');
  const [submittingFeedback, setSubmittingFeedback] = useState(false);
  const [actionProcessing, setActionProcessing] = useState(null);
  const [actionError, setActionError] = useState('');
  const [showReferralForm, setShowReferralForm] = useState(false);
  const [referralForm, setReferralForm] = useState({
    patient_first_name: '',
    patient_last_name: '',
    gender: 'male',
    date_of_birth: '',
    phone: '',
    address: '',
    national_id: '',
    patient_number: '',
    age_years: '',
    receiving_facility_id: '',
    receiving_department_id: '',
    assigned_doctor_id: '',
    region: '',
    district: '',
    transfer_date: '',
    referral_number: '',
    urgency: 'routine',
    diagnosis: '',
    temperature: '',
    heart_rate: '',
    respiratory_rate: '',
    blood_pressure: '',
    mental_status: '',
    treatment_before_transfer: '',
    reason_for_transfer: '',
    clinical_reason: '',
    clinical_findings: '',
    requested_services: '',
  });
  const [departments, setDepartments] = useState([]);
  const [doctors, setDoctors] = useState([]);
  const [referralDoctors, setReferralDoctors] = useState([]);
  const [referralMessage, setReferralMessage] = useState('');
  const [submittingReferral, setSubmittingReferral] = useState(false);
  const [selectedReferral, setSelectedReferral] = useState(null);
  const [messages, setMessages] = useState([]);
  const [messageRecipients, setMessageRecipients] = useState([]);
  const [messageForm, setMessageForm] = useState({ recipient_id: '', message: '' });
  const [sendingMessage, setSendingMessage] = useState(false);
  const [messageError, setMessageError] = useState('');
  const [loadingMessages, setLoadingMessages] = useState(false);
  const [assigningDoctor, setAssigningDoctor] = useState(false);
  const [assignmentMessage, setAssignmentMessage] = useState('');
  const [selectedDoctorIdForAssignment, setSelectedDoctorIdForAssignment] = useState('');

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const requestedTab = params.get('tab');
    if (requestedTab && Object.keys(sectionMeta).includes(requestedTab)) {
      setActiveTab(requestedTab);
    }
  }, []);

  useEffect(() => {
    if (!selectedReferral) {
      return undefined;
    }

    const handleKeyDown = (event) => {
      if (event.key === 'Escape') {
        setSelectedReferral(null);
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [selectedReferral]);

  useEffect(() => {
    async function fetchSummary() {
      setSectionErrors((prev) => ({ ...prev, overview: '' }));
      setLoadingState((prev) => ({ ...prev, overview: true }));
      try {
        const response = await fetch(apiUrl('/dashboard/summary.php'), {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setSummary(data.summary);
        } else {
          setSectionErrors((prev) => ({
            ...prev,
            overview: data.error || 'Unable to load dashboard summary.',
          }));
        }
      } catch (err) {
        setSectionErrors((prev) => ({
          ...prev,
          overview: 'Unable to reach the dashboard service.',
        }));
      } finally {
        setLoadingState((prev) => ({ ...prev, overview: false }));
      }
    }

    if (!summary) {
      fetchSummary();
    }
  }, [summary]);

  useEffect(() => {
    async function fetchReferrals() {
      setSectionErrors((prev) => ({ ...prev, referrals: '' }));
      setLoadingState((prev) => ({ ...prev, referrals: true }));
      try {
        const response = await fetch(apiUrl('/referrals/list.php'), {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setReferrals(data.referrals);
        } else {
          setSectionErrors((prev) => ({
            ...prev,
            referrals: data.error || 'Unable to load referrals.',
          }));
        }
      } catch (err) {
        setSectionErrors((prev) => ({
          ...prev,
          referrals: 'Unable to reach referral service.',
        }));
      } finally {
        setLoadingState((prev) => ({ ...prev, referrals: false }));
      }
    }

    async function fetchFacilities() {
      setSectionErrors((prev) => ({ ...prev, facilities: '' }));
      setLoadingState((prev) => ({ ...prev, facilities: true }));
      try {
        const response = await fetch(apiUrl('/facilities/manage_facilities.php'), {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setFacilities(data.facilities);
        } else {
          setSectionErrors((prev) => ({
            ...prev,
            facilities: data.error || 'Unable to load facilities.',
          }));
        }
      } catch (err) {
        setSectionErrors((prev) => ({
          ...prev,
          facilities: 'Unable to reach facilities service.',
        }));
      } finally {
        setLoadingState((prev) => ({ ...prev, facilities: false }));
      }
    }

    async function fetchPatients() {
      setSectionErrors((prev) => ({ ...prev, patients: '' }));
      setLoadingState((prev) => ({ ...prev, patients: true }));
      try {
        const response = await fetch(apiUrl('/patients/list.php'), {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setPatients(data.patients);
        } else {
          setSectionErrors((prev) => ({
            ...prev,
            patients: data.error || 'Unable to load patients.',
          }));
        }
      } catch (err) {
        setSectionErrors((prev) => ({
          ...prev,
          patients: 'Unable to reach patient service.',
        }));
      } finally {
        setLoadingState((prev) => ({ ...prev, patients: false }));
      }
    }

    async function fetchFeedback() {
      setSectionErrors((prev) => ({ ...prev, feedback: '' }));
      setLoadingState((prev) => ({ ...prev, feedback: true }));
      try {
        const response = await fetch(apiUrl('/feedback/list.php'), {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setFeedbackItems(data.feedback);
        } else {
          setSectionErrors((prev) => ({
            ...prev,
            feedback: data.error || 'Unable to load feedback.',
          }));
        }
      } catch (err) {
        setSectionErrors((prev) => ({
          ...prev,
          feedback: 'Unable to reach feedback service.',
        }));
      } finally {
        setLoadingState((prev) => ({ ...prev, feedback: false }));
      }
    }

    async function fetchNotifications() {
      setSectionErrors((prev) => ({ ...prev, notifications: '' }));
      setLoadingState((prev) => ({ ...prev, notifications: true }));
      try {
        const response = await fetch(apiUrl('/notifications/list.php'), {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setNotifications(data.notifications);
        } else {
          setSectionErrors((prev) => ({
            ...prev,
            notifications: data.error || 'Unable to load notifications.',
          }));
        }
      } catch (err) {
        setSectionErrors((prev) => ({
          ...prev,
          notifications: 'Unable to reach notifications service.',
        }));
      } finally {
        setLoadingState((prev) => ({ ...prev, notifications: false }));
      }
    }

    if (activeTab === 'referrals' && referrals.length === 0) {
      fetchReferrals();
    }

    if (activeTab === 'patients' && patients.length === 0) {
      fetchPatients();
    }

    if (activeTab === 'facilities' && facilities.length === 0) {
      fetchFacilities();
    }

    if (activeTab === 'referrals' && facilities.length === 0) {
      fetchFacilities();
    }

    if (activeTab === 'feedback' && feedbackItems.length === 0) {
      fetchFeedback();
    }

    if (activeTab === 'feedback' && referrals.length === 0 && summary?.user?.role === 'receptionist') {
      fetchReferrals();
    }

    if (activeTab === 'notifications' && notifications.length === 0) {
      fetchNotifications();
    }
  }, [activeTab, referrals.length, patients.length, facilities.length, feedbackItems.length, notifications.length, summary?.user?.role]);

  useEffect(() => {
    async function fetchNotificationDepartments(referralId) {
      if (!referralId) {
        setNotificationDepartments([]);
        setNotificationDoctors([]);
        return;
      }

      const referral = referrals.find((item) => item.id === Number(referralId));
      if (!referral) {
        setNotificationDepartments([]);
        setNotificationDoctors([]);
        return;
      }

      try {
        const response = await fetch(apiUrl(`/departments/list.php?facility_id=${referral.receiving_facility_id}`), {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setNotificationDepartments(data.departments);
        } else {
          setNotificationDepartments([]);
        }
      } catch (err) {
        setNotificationDepartments([]);
      }
      setNotificationDoctors([]);
    }

    fetchNotificationDepartments(notificationForm.referral_id);
  }, [notificationForm.referral_id, referrals]);

  useEffect(() => {
    async function fetchNotificationDoctors(facilityId, departmentId) {
      if (!facilityId || !departmentId) {
        setNotificationDoctors([]);
        return;
      }

      try {
        const response = await fetch(apiUrl(`/doctors/list.php?facility_id=${facilityId}&department_id=${departmentId}`), {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setNotificationDoctors(data.doctors);
        } else {
          setNotificationDoctors([]);
        }
      } catch (err) {
        setNotificationDoctors([]);
      }
    }

    const referral = referrals.find((item) => item.id === Number(notificationForm.referral_id));
    if (referral && notificationForm.department_id) {
      fetchNotificationDoctors(referral.receiving_facility_id, notificationForm.department_id);
    } else {
      setNotificationDoctors([]);
    }
  }, [notificationForm.department_id, notificationForm.referral_id, referrals]);

  useEffect(() => {
    async function fetchDepartments(facilityId) {
      if (!facilityId) {
        setDepartments([]);
        return;
      }

      try {
        const response = await fetch(apiUrl(`/departments/list.php?facility_id=${facilityId}`), {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setDepartments(data.departments);
        } else {
          setDepartments([]);
        }
      } catch (err) {
        setDepartments([]);
      }
    }

    async function fetchDoctors(facilityId, departmentId) {
      if (!facilityId) {
        setDoctors([]);
        return;
      }

      const query = `facility_id=${facilityId}${departmentId ? `&department_id=${departmentId}` : ''}`;
      try {
        const response = await fetch(apiUrl(`/doctors/list.php?${query}`), {
          method: 'GET',
          credentials: 'include',
        });
        const data = await response.json();
        if (response.ok && data.success) {
          setDoctors(data.doctors);
        } else {
          setDoctors([]);
        }
      } catch (err) {
        setDoctors([]);
      }
    }

    fetchDepartments(referralForm.receiving_facility_id);
    fetchDoctors(referralForm.receiving_facility_id, referralForm.receiving_department_id);
  }, [referralForm.receiving_facility_id, referralForm.receiving_department_id]);
  
  const submitReferral = async (event) => {
    event.preventDefault();
    setSubmittingReferral(true);
    setReferralMessage('');

    try {
      const response = await fetch(apiUrl('/referrals/create.php'), {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(referralForm),
      });

      const data = await response.json();
      if (response.ok && data.success) {
        setReferralMessage('Referral created successfully. Refreshing list...');
        setShowReferralForm(false);
        setReferralForm({
          patient_first_name: '',
          patient_last_name: '',
          gender: 'male',
          date_of_birth: '',
          phone: '',
          address: '',
          national_id: '',
          patient_number: '',
          age_years: '',
          receiving_facility_id: '',
          region: '',
          district: '',
          transfer_date: '',
          referral_number: '',
          urgency: 'routine',
          diagnosis: '',
          temperature: '',
          heart_rate: '',
          respiratory_rate: '',
          blood_pressure: '',
          mental_status: '',
          treatment_before_transfer: '',
          reason_for_transfer: '',
          clinical_reason: '',
          clinical_findings: '',
          requested_services: '',
        });
        setReferrals([]);
      } else {
        setReferralMessage(data.error || 'Unable to create referral.');
      }
    } catch (err) {
      setReferralMessage('Unable to reach referral service.');
    }

    setSubmittingReferral(false);
  };

  const updateReferralStatus = async (referralId, status) => {
    setActionProcessing(referralId);
    setActionError('');
    try {
      const response = await fetch(apiUrl('/referrals/update_status.php'), {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ referral_id: referralId, status }),
      });
      const data = await response.json();
      if (response.ok && data.success) {
        setReferrals([]); // trigger refresh
      } else {
        setActionError(data.error || data.message || 'Unable to update status');
      }
    } catch (err) {
      setActionError('Unable to reach update service.');
    }
    setActionProcessing(null);
  };

  const loadMessagesForReferral = async (referralId) => {
    setLoadingMessages(true);
    try {
      const response = await fetch(apiUrl(`/communications/list.php?referral_id=${referralId}`), {
        method: 'GET',
        credentials: 'include',
      });
      const data = await response.json();
      if (response.ok && data.success) {
        setMessages(data.messages);
      } else {
        setMessages([]);
      }
    } catch (err) {
      setMessages([]);
    }
    setLoadingMessages(false);
  };

  const loadReferralDoctors = async (referral) => {
    if (!referral?.receiving_facility_id) {
      setReferralDoctors([]);
      return;
    }

    const query = `facility_id=${referral.receiving_facility_id}${referral.receiving_department_id ? `&department_id=${referral.receiving_department_id}` : ''}`;
    try {
      const response = await fetch(apiUrl(`/doctors/list.php?${query}`), {
        method: 'GET',
        credentials: 'include',
      });
      const data = await response.json();
      if (response.ok && data.success) {
        setReferralDoctors(data.doctors);
      } else {
        setReferralDoctors([]);
      }
    } catch (err) {
      setReferralDoctors([]);
    }
  };

  const loadMessageRecipients = (referral) => {
    if (!referral) {
      setMessageRecipients([]);
      return;
    }

    const recipients = referralDoctors.map((doctor) => ({
      id: doctor.user_id,
      label: `Doctor: ${doctor.full_name} (${doctor.department_name})`,
    }));

    setMessageRecipients(recipients);
  };

  useEffect(() => {
    if (!selectedReferral) {
      setMessages([]);
      setMessageRecipients([]);
      setMessageForm({ recipient_id: '', message: '' });
      setMessageError('');
      setReferralDoctors([]);
      setSelectedDoctorIdForAssignment('');
      setAssignmentMessage('');
      return;
    }

    loadMessagesForReferral(selectedReferral.id);
    loadReferralDoctors(selectedReferral);
  }, [selectedReferral, summary?.user?.role]);

  useEffect(() => {
    if (selectedReferral) {
      loadMessageRecipients(selectedReferral);
    }
  }, [selectedReferral, referralDoctors]);

  const sendMessage = async (event) => {
    event.preventDefault();
    setSendingMessage(true);
    setMessageError('');

    try {
      const response = await fetch(apiUrl('/communications/create.php'), {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          referral_id: selectedReferral.id,
          recipient_id: messageForm.recipient_id,
          message: messageForm.message,
        }),
      });
      const data = await response.json();
      if (response.ok && data.success) {
        setMessageForm({ recipient_id: '', message: '' });
        await loadMessagesForReferral(selectedReferral.id);
      } else {
        setMessageError(data.error || 'Unable to send message.');
      }
    } catch (err) {
      setMessageError('Unable to reach communications service.');
    }
    setSendingMessage(false);
  };

  const assignDoctorToReferral = async (referralId) => {
    if (!selectedDoctorIdForAssignment) {
      setAssignmentMessage('Please choose a doctor to assign.');
      return;
    }

    setAssigningDoctor(true);
    setAssignmentMessage('');
    try {
      const response = await fetch(apiUrl('/referrals/assign_doctor.php'), {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ referral_id: referralId, doctor_id: selectedDoctorIdForAssignment }),
      });
      const data = await response.json();
      if (response.ok && data.success) {
        setAssignmentMessage('Doctor assigned successfully. Refreshing referral...');
        setSelectedReferral(null);
        setReferrals([]);
      } else {
        setAssignmentMessage(data.error || 'Unable to assign doctor.');
      }
    } catch (err) {
      setAssignmentMessage('Unable to reach assign doctor service.');
    }
    setAssigningDoctor(false);
  };

  const sendNotification = async (event) => {
    event.preventDefault();
    setSendingNotification(true);
    setNotificationMessage('');
    setNotificationError('');

    try {
      const response = await fetch(apiUrl('/notifications/create.php'), {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(notificationForm),
      });
      const data = await response.json();
      if (response.ok && data.success) {
        setNotificationMessage('Notification created successfully.');
        setNotificationForm((prev) => ({
          ...prev,
          department_id: '',
          recipient_doctor_id: '',
          notification_type: 'email',
          note: '',
        }));
        setNotifications([]);
      } else {
        setNotificationError(data.error || 'Unable to create notification.');
      }
    } catch (err) {
      setNotificationError('Unable to reach notification service.');
    }

    setSendingNotification(false);
  };

  const submitFeedback = async (event) => {
    event.preventDefault();
    setSubmittingFeedback(true);
    setFeedbackMessage('');

    try {
      const response = await fetch(apiUrl('/feedback/create.php'), {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(feedbackForm),
      });
      const data = await response.json();
      if (response.ok && data.success) {
        setFeedbackMessage('Feedback submitted successfully.');
        setFeedbackForm((prev) => ({
          ...prev,
          department: '',
          referral_serial_no: '',
          referral_diagnosis: '',
          confirmed_diagnosis: '',
          comments: '',
          clinical_outcome: '',
          treatment_given: '',
          discharge_summary: '',
          follow_up_instructions: '',
        }));
        setFeedbackItems([]);
      } else {
        setFeedbackMessage(data.error || 'Unable to submit feedback.');
      }
    } catch (err) {
      setFeedbackMessage('Unable to reach feedback service.');
    }

    setSubmittingFeedback(false);
  };

  useEffect(() => {
    setContentVisible(false);
    const timeout = window.setTimeout(() => {
      setContentVisible(true);
    }, 120);
    return () => window.clearTimeout(timeout);
  }, [activeTab]);

  useEffect(() => {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', activeTab);
    window.history.replaceState(null, '', url.toString());
  }, [activeTab]);

  const signOut = async (event) => {
    event.preventDefault();
    await fetch(apiUrl('/auth/logout.php'), {
      method: 'POST',
      credentials: 'include',
    });
    window.location.href = '/';
  };

  return (
    <main className="container dashboard-page">
      <section className="section dashboard-section">
        <div className="nav-top">
          <div>
            <DashboardNav activeTab={activeTab} onChange={setActiveTab} />
          </div>
          <a href="/" className="button-secondary" onClick={signOut}>Sign Out</a>
        </div>

        <div className="section-title">
          <span>{sectionMeta[activeTab].title}</span>
          <h2>{sectionMeta[activeTab].subtitle}</h2>
        </div>

        <div className={`dashboard-section-content ${contentVisible ? 'visible' : 'hidden'}`}>

        {activeTab === 'overview' && (
          loadingState.overview ? (
            <p className="dashboard-message">Loading overview...</p>
          ) : sectionErrors.overview ? (
            <p className="dashboard-message dashboard-error">{sectionErrors.overview}</p>
          ) : (
            <div className="dashboard-grid">
              <div className="dashboard-card highlight-card">
                <span>Total Referrals</span>
                <strong>{summary?.referrals ?? 0}</strong>
              </div>
              <div className="dashboard-card">
                <span>Pending Referrals</span>
                <strong>{summary?.pending_referrals ?? 0}</strong>
              </div>
              <div className="dashboard-card">
                <span>Facilities</span>
                <strong>{summary?.facilities ?? 0}</strong>
              </div>
              <div className="dashboard-card">
                <span>Active Users</span>
                <strong>{summary?.active_users ?? 0}</strong>
              </div>
            </div>
          )
        )}

        {activeTab === 'referrals' && (
          loadingState.referrals ? (
            <p className="dashboard-message">Loading referrals...</p>
          ) : sectionErrors.referrals ? (
            <p className="dashboard-message dashboard-error">{sectionErrors.referrals}</p>
          ) : (
            <>
              {summary?.user?.role === 'co' && (
                <div className="form-card" style={{ marginBottom: '1.75rem' }}>
                  <div className="nav-top" style={{ alignItems: 'center', justifyContent: 'space-between', marginBottom: '1rem' }}>
                    <div>
                      <h3 style={{ margin: 0 }}>Create a new referral</h3>
                      <p style={{ margin: '0.5rem 0 0', color: '#475569' }}>
                        Submit a new referral to another facility in the network.
                      </p>
                    </div>
                    <button
                      type="button"
                      className="button button-secondary"
                      onClick={() => {
                        setShowReferralForm((prev) => !prev);
                        setReferralMessage('');
                      }}
                    >
                      {showReferralForm ? 'Hide form' : 'New referral'}
                    </button>
                  </div>

                  {showReferralForm && (
                    <form onSubmit={submitReferral} className="login-form">
                      <div className="form-field">
                        <span>Patient First Name</span>
                        <input
                          type="text"
                          name="patient_first_name"
                          value={referralForm.patient_first_name}
                          onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                          required
                          className="form-input"
                        />
                      </div>
                      <div className="form-field">
                        <span>Patient Last Name</span>
                        <input
                          type="text"
                          name="patient_last_name"
                          value={referralForm.patient_last_name}
                          onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                          required
                          className="form-input"
                        />
                      </div>
                      <div className="form-field">
                        <span>Gender</span>
                        <select
                          name="gender"
                          value={referralForm.gender}
                          onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                          className="form-input"
                        >
                          <option value="male">Male</option>
                          <option value="female">Female</option>
                          <option value="other">Other</option>
                        </select>
                      </div>
                      <div className="form-field">
                        <span>Date of birth</span>
                        <input
                          type="date"
                          name="date_of_birth"
                          value={referralForm.date_of_birth}
                          onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                          className="form-input"
                        />
                      </div>
                      <div className="form-field">
                        <span>Phone</span>
                        <input
                          type="tel"
                          name="phone"
                          value={referralForm.phone}
                          onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                          className="form-input"
                        />
                      </div>
                      {referralExtraSections.map((section) => (
                        <div key={section.title} className="form-subsection">
                          <h4>{section.title}</h4>
                          <div className="form-grid">
                            {section.fields.map((field) => (
                              <label key={field.name} className="form-field">
                                <span>{field.label}</span>
                                {field.type === 'textarea' ? (
                                  <textarea
                                    name={field.name}
                                    value={referralForm[field.name]}
                                    onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                                    className="form-input"
                                    rows="2"
                                  />
                                ) : (
                                  <input
                                    type={field.type}
                                    name={field.name}
                                    value={referralForm[field.name]}
                                    onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                                    className="form-input"
                                  />
                                )}
                              </label>
                            ))}
                          </div>
                        </div>
                      ))}
                      <div className="form-field">
                        <span>Receiving facility</span>
                        <select
                          name="receiving_facility_id"
                          value={referralForm.receiving_facility_id}
                          onChange={(e) => setReferralForm((prev) => ({
                            ...prev,
                            [e.target.name]: e.target.value,
                            receiving_department_id: '',
                            assigned_doctor_id: '',
                          }))}
                          required
                          className="form-input"
                        >
                          <option value="">Choose facility</option>
                          {facilities
                            .filter((facility) => facility.id !== summary?.user?.facility_id)
                            .map((facility) => (
                              <option key={facility.id} value={facility.id}>
                                {facility.name} ({facility.region})
                              </option>
                            ))}
                        </select>
                      </div>
                      <div className="form-field">
                        <span>Receiving department</span>
                        <select
                          name="receiving_department_id"
                          value={referralForm.receiving_department_id}
                          onChange={(e) => setReferralForm((prev) => ({
                            ...prev,
                            [e.target.name]: e.target.value,
                            assigned_doctor_id: '',
                          }))}
                          required
                          className="form-input"
                          disabled={departments.length === 0}
                        >
                          <option value="">Choose department</option>
                          {departments.map((department) => (
                            <option key={department.id} value={department.id}>
                              {department.name}
                            </option>
                          ))}
                        </select>
                      </div>
                      <div className="form-field">
                        <span>Assign doctor (optional)</span>
                        <select
                          name="assigned_doctor_id"
                          value={referralForm.assigned_doctor_id}
                          onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                          className="form-input"
                          disabled={doctors.length === 0}
                        >
                          <option value="">Choose doctor</option>
                          {doctors.map((doctor) => (
                            <option key={doctor.id} value={doctor.id}>
                              {doctor.full_name} — {doctor.department_name}
                            </option>
                          ))}
                        </select>
                      </div>
                      <div className="form-field">
                        <span>Urgency</span>
                        <select
                          name="urgency"
                          value={referralForm.urgency}
                          onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                          className="form-input"
                        >
                          <option value="routine">Routine</option>
                          <option value="urgent">Urgent</option>
                          <option value="emergency">Emergency</option>
                        </select>
                      </div>
                      <div className="form-field">
                        <span>Patient condition / clinical reason</span>
                        <textarea
                          name="clinical_reason"
                          value={referralForm.clinical_reason}
                          onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                          required
                          className="form-input"
                          rows="3"
                        />
                      </div>
                      <div className="form-field">
                        <span>Clinical findings</span>
                        <textarea
                          name="clinical_findings"
                          value={referralForm.clinical_findings}
                          onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                          className="form-input"
                          rows="3"
                        />
                      </div>
                      <div className="form-field">
                        <span>Requested services</span>
                        <textarea
                          name="requested_services"
                          value={referralForm.requested_services}
                          onChange={(e) => setReferralForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))}
                          className="form-input"
                          rows="2"
                        />
                      </div>
                      {referralMessage && (
                        <p className="dashboard-message" style={{ marginBottom: '1rem' }}>
                          {referralMessage}
                        </p>
                      )}
                      <button type="submit" className="button" disabled={submittingReferral}>
                        {submittingReferral ? 'Submitting...' : 'Submit Referral'}
                      </button>
                    </form>
                  )}
                </div>
              )}
              <div className="table-card">
                {referrals.length === 0 ? (
                  <p>No referrals available yet.</p>
                ) : (
                  <table className="data-table">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Condition</th>
                        <th>Urgency</th>
                        <th>Status</th>
                        <th>From / To</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {referrals.map((item) => (
                        <tr key={item.id}>
                          <td>{item.id}</td>
                          <td>{item.patient_name}</td>
                          <td className="referral-summary-cell">
                            <p>{item.clinical_reason || 'No condition details recorded.'}</p>
                            <button
                              type="button"
                              className="table-icon-button"
                              onClick={() => setSelectedReferral(item)}
                            >
                              <i className="fa-solid fa-eye" aria-hidden="true"></i>
                              View details
                            </button>
                          </td>
                          <td>{item.urgency}</td>
                          <td className={`status-pill status-${item.status}`}>
                            {item.status}
                          </td>
                          <td>
                            <span className="facility-route">
                              <span>{item.referring_facility}</span>
                              <i className="fa-solid fa-arrow-right" aria-hidden="true"></i>
                              <span>{item.receiving_facility}</span>
                            </span>
                          </td>
                          <td>{new Date(item.created_at).toLocaleDateString()}</td>
                          <td>
                            {summary?.user?.role === 'receptionist' && (
                              <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
                                {item.status === 'pending' && (
                                  <>
                                    <button className="button" disabled={actionProcessing === item.id} onClick={() => updateReferralStatus(item.id, 'accepted')}>Accept</button>
                                    <button className="button button-secondary" disabled={actionProcessing === item.id} onClick={() => {
                                      const reason = window.prompt('Rejection reason (optional):');
                                      updateReferralStatus(item.id, 'rejected');
                                    }}>Reject</button>
                                  </>
                                )}
                                {item.status === 'accepted' && (
                                  <button className="button" disabled={actionProcessing === item.id} onClick={() => updateReferralStatus(item.id, 'in_progress')}>Start</button>
                                )}
                                {item.status === 'in_progress' && (
                                  <button className="button" disabled={actionProcessing === item.id} onClick={() => updateReferralStatus(item.id, 'completed')}>Complete</button>
                                )}
                              </div>
                            )}
                            {actionError && actionProcessing === null && <p className="dashboard-message dashboard-error">{actionError}</p>}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                )}
              </div>
            </>
          )
        )}

        {activeTab === 'patients' && (
          loadingState.patients ? (
            <p className="dashboard-message">Loading patients...</p>
          ) : sectionErrors.patients ? (
            <p className="dashboard-message dashboard-error">{sectionErrors.patients}</p>
          ) : (
            <div className="table-card">
              {patients.length === 0 ? (
                <p>No patient records available yet.</p>
              ) : (
                <table className="data-table">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Patient</th>
                      <th>Gender</th>
                      <th>Date of Birth</th>
                      <th>Phone</th>
                      <th>Referrals</th>
                      <th>Last Referral</th>
                    </tr>
                  </thead>
                  <tbody>
                    {patients.map((patient) => (
                      <tr key={patient.id}>
                        <td>{patient.id}</td>
                        <td>
                          <strong>{patient.patient_name}</strong>
                          {patient.national_id && (
                            <span style={{ display: 'block', color: '#64748b', fontSize: '0.9rem' }}>
                              ID: {patient.national_id}
                            </span>
                          )}
                        </td>
                        <td>{patient.gender || 'Not set'}</td>
                        <td>{patient.date_of_birth || 'Not set'}</td>
                        <td>{patient.phone || 'Not set'}</td>
                        <td>{patient.referral_count}</td>
                        <td>
                          {patient.last_referral_at
                            ? new Date(patient.last_referral_at).toLocaleDateString()
                            : 'No referral'}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>
          )
        )}

        {activeTab === 'facilities' && (
          loadingState.facilities ? (
            <p className="dashboard-message">Loading facilities...</p>
          ) : sectionErrors.facilities ? (
            <p className="dashboard-message dashboard-error">{sectionErrors.facilities}</p>
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
          )
        )}

        {activeTab === 'feedback' && (
          loadingState.feedback ? (
            <p className="dashboard-message">Loading feedback...</p>
          ) : sectionErrors.feedback ? (
            <p className="dashboard-message dashboard-error">{sectionErrors.feedback}</p>
          ) : (
            <div className="grid-card feedback-grid">
              {summary?.user?.role === 'receptionist' && (
                <div className="feedback-card" style={{ marginBottom: '1.5rem' }}>
                  <h3>Submit Clinical Feedback</h3>
                  <form onSubmit={submitFeedback}>
                    <div className="form-field">
                      <span>Referral</span>
                      <select
                        name="referral_id"
                        value={feedbackForm.referral_id}
                        onChange={(e) => setFeedbackForm((prev) => ({ ...prev, referral_id: e.target.value }))}
                        className="form-input"
                        required
                      >
                        <option value="">Select a referral</option>
                        {referrals.map((ref) => (
                          <option key={ref.id} value={ref.id}>
                            #{ref.id} - {ref.patient_name} to {ref.receiving_facility} ({ref.status})
                          </option>
                        ))}
                      </select>
                    </div>
                    <div className="form-field">
                      <span>Clinical outcome</span>
                      <textarea
                        name="clinical_outcome"
                        value={feedbackForm.clinical_outcome}
                        onChange={(e) => setFeedbackForm((prev) => ({ ...prev, clinical_outcome: e.target.value }))}
                        className="form-input"
                        rows="2"
                        required
                      />
                    </div>
                    <div className="form-field">
                      <span>Department</span>
                      <input
                        type="text"
                        name="department"
                        value={feedbackForm.department}
                        onChange={(e) => setFeedbackForm((prev) => ({ ...prev, department: e.target.value }))}
                        className="form-input"
                      />
                    </div>
                    <div className="form-field">
                      <span>Referral Serial No.</span>
                      <input
                        type="text"
                        name="referral_serial_no"
                        value={feedbackForm.referral_serial_no}
                        onChange={(e) => setFeedbackForm((prev) => ({ ...prev, referral_serial_no: e.target.value }))}
                        className="form-input"
                      />
                    </div>
                    <div className="form-field">
                      <span>Referral Diagnosis</span>
                      <textarea
                        name="referral_diagnosis"
                        value={feedbackForm.referral_diagnosis}
                        onChange={(e) => setFeedbackForm((prev) => ({ ...prev, referral_diagnosis: e.target.value }))}
                        className="form-input"
                        rows="2"
                      />
                    </div>
                    <div className="form-field">
                      <span>Confirmed Diagnosis</span>
                      <textarea
                        name="confirmed_diagnosis"
                        value={feedbackForm.confirmed_diagnosis}
                        onChange={(e) => setFeedbackForm((prev) => ({ ...prev, confirmed_diagnosis: e.target.value }))}
                        className="form-input"
                        rows="2"
                      />
                    </div>
                    <div className="form-field">
                      <span>Treatment given</span>
                      <textarea
                        name="treatment_given"
                        value={feedbackForm.treatment_given}
                        onChange={(e) => setFeedbackForm((prev) => ({ ...prev, treatment_given: e.target.value }))}
                        className="form-input"
                        rows="2"
                      />
                    </div>
                    <div className="form-field">
                      <span>Discharge summary</span>
                      <textarea
                        name="discharge_summary"
                        value={feedbackForm.discharge_summary}
                        onChange={(e) => setFeedbackForm((prev) => ({ ...prev, discharge_summary: e.target.value }))}
                        className="form-input"
                        rows="2"
                      />
                    </div>
                    <div className="form-field">
                      <span>Follow-up instructions</span>
                      <textarea
                        name="follow_up_instructions"
                        value={feedbackForm.follow_up_instructions}
                        onChange={(e) => setFeedbackForm((prev) => ({ ...prev, follow_up_instructions: e.target.value }))}
                        className="form-input"
                        rows="2"
                      />
                    </div>
                    <div className="form-field">
                      <span>Comments</span>
                      <textarea
                        name="comments"
                        value={feedbackForm.comments}
                        onChange={(e) => setFeedbackForm((prev) => ({ ...prev, comments: e.target.value }))}
                        className="form-input"
                        rows="3"
                      />
                    </div>
                    {feedbackMessage && (
                      <p className="dashboard-message" style={{ marginBottom: '1rem' }}>
                        {feedbackMessage}
                      </p>
                    )}
                    <button type="submit" className="button" disabled={submittingFeedback}>
                      {submittingFeedback ? 'Sending feedback...' : 'Send feedback'}
                    </button>
                  </form>
                </div>
              )}
              {feedbackItems.length === 0 ? (
                <p>No feedback records available yet.</p>
              ) : (
                feedbackItems.map((item) => (
                  <div key={item.id} className="feedback-card">
                    <div className="feedback-meta">
                      <strong>Referral #{item.referral_id}</strong>
                      <span>{new Date(item.sent_at).toLocaleDateString()}</span>
                    </div>
                    <p><strong>Patient:</strong> {item.patient_name}</p>
                    <p><strong>Sent by:</strong> {item.sent_by}</p>
                    <p><strong>Department:</strong> {item.department || 'Not provided'}</p>
                    <p><strong>Referral Serial No.:</strong> {item.referral_serial_no || 'Not provided'}</p>
                    <p><strong>Referral Diagnosis:</strong> {item.referral_diagnosis || 'Not provided'}</p>
                    <p><strong>Confirmed Diagnosis:</strong> {item.confirmed_diagnosis || 'Not provided'}</p>
                    <p><strong>Outcome:</strong> {item.clinical_outcome}</p>
                    <p><strong>Treatment:</strong> {item.treatment_given}</p>
                    <p><strong>Summary:</strong> {item.discharge_summary}</p>
                    <p><strong>Comments:</strong> {item.comments || 'Not provided'}</p>
                  </div>
                ))
              )}
            </div>
          )
        )}

        {activeTab === 'notifications' && (
          loadingState.notifications ? (
            <p className="dashboard-message">Loading notifications...</p>
          ) : sectionErrors.notifications ? (
            <p className="dashboard-message dashboard-error">{sectionErrors.notifications}</p>
          ) : (
            <>
              {summary?.user?.role === 'receptionist' && (
                <div className="form-card" style={{ marginBottom: '1.75rem' }}>
                  <h3>Create Referral Notification</h3>
                  <form onSubmit={sendNotification} className="login-form">
                    <div className="form-field">
                      <span>Referral</span>
                      <select
                        name="referral_id"
                        value={notificationForm.referral_id}
                        onChange={(e) => setNotificationForm((prev) => ({ ...prev, referral_id: e.target.value, department_id: '', recipient_doctor_id: '' }))}
                        className="form-input"
                        required
                      >
                        <option value="">Select referral</option>
                        {referrals.map((ref) => (
                          <option key={ref.id} value={ref.id}>
                            #{ref.id} - {ref.patient_name} to {ref.receiving_facility}
                          </option>
                        ))}
                      </select>
                    </div>
                    <div className="form-field">
                      <span>Department</span>
                      <select
                        name="department_id"
                        value={notificationForm.department_id}
                        onChange={(e) => setNotificationForm((prev) => ({ ...prev, department_id: e.target.value, recipient_doctor_id: '' }))}
                        className="form-input"
                        required
                        disabled={!notificationForm.referral_id || notificationDepartments.length === 0}
                      >
                        <option value="">Select department</option>
                        {notificationDepartments.map((dept) => (
                          <option key={dept.id} value={dept.id}>
                            {dept.name}
                          </option>
                        ))}
                      </select>
                    </div>
                    <div className="form-field">
                      <span>Doctor</span>
                      <select
                        name="recipient_doctor_id"
                        value={notificationForm.recipient_doctor_id}
                        onChange={(e) => setNotificationForm((prev) => ({ ...prev, recipient_doctor_id: e.target.value }))}
                        className="form-input"
                        required
                        disabled={!notificationForm.department_id || notificationDoctors.length === 0}
                      >
                        <option value="">Select doctor</option>
                        {notificationDoctors.map((doctor) => (
                          <option key={doctor.id} value={doctor.id}>
                            {doctor.full_name}
                          </option>
                        ))}
                      </select>
                    </div>
                    <div className="form-field">
                      <span>Notification type</span>
                      <select
                        name="notification_type"
                        value={notificationForm.notification_type}
                        onChange={(e) => setNotificationForm((prev) => ({ ...prev, notification_type: e.target.value }))}
                        className="form-input"
                      >
                        <option value="email">Email</option>
                        <option value="sms">SMS</option>
                        <option value="both">Email + SMS</option>
                      </select>
                    </div>
                    <div className="form-field">
                      <span>Message note</span>
                      <textarea
                        name="note"
                        value={notificationForm.note}
                        onChange={(e) => setNotificationForm((prev) => ({ ...prev, note: e.target.value }))}
                        className="form-input"
                        rows="3"
                      />
                    </div>
                    {notificationError && (
                      <p className="dashboard-message dashboard-error">{notificationError}</p>
                    )}
                    {notificationMessage && (
                      <p className="dashboard-message">{notificationMessage}</p>
                    )}
                    <button type="submit" className="button" disabled={sendingNotification || !notificationForm.referral_id || !notificationForm.department_id || !notificationForm.recipient_doctor_id}>
                      {sendingNotification ? 'Sending...' : 'Send notification'}
                    </button>
                  </form>
                </div>
              )}

              <div className="grid-card notification-grid">
                {notifications.length === 0 ? (
                  <p>No notifications available yet.</p>
                ) : (
                  notifications.map((item) => (
                    <div key={item.id} className="notification-card">
                      <div className="notification-meta">
                        <span className={`notification-type type-${item.type}`}>{item.type.toUpperCase()}</span>
                        <span>{new Date(item.sent_at).toLocaleString()}</span>
                      </div>
                      <p><strong>Subject:</strong> {item.subject}</p>
                      <p><strong>Recipient:</strong> {item.recipient_email || item.recipient_phone}</p>
                      <p><strong>Status:</strong> {item.status}</p>
                      {item.referral_id && <p><strong>Referral:</strong> #{item.referral_id} ({item.patient_name})</p>}
                    </div>
                  ))
                )}
              </div>
            </>
          )
        )}
        </div>
      </section>

      {selectedReferral && (
        <div
          className="modal-backdrop"
          role="presentation"
          onMouseDown={(event) => {
            if (event.target === event.currentTarget) {
              setSelectedReferral(null);
            }
          }}
        >
          <section
            className="referral-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="referral-modal-title"
          >
            <div className="modal-header">
              <div>
                <span>Referral #{selectedReferral.id}</span>
                <h3 id="referral-modal-title">{selectedReferral.patient_name}</h3>
              </div>
              <button
                type="button"
                className="modal-close-button"
                aria-label="Close referral details"
                onClick={() => setSelectedReferral(null)}
              >
                <i className="fa-solid fa-xmark" aria-hidden="true"></i>
              </button>
            </div>

            <div className="modal-meta-grid">
              <div>
                <span>Status</span>
                <strong className={`status-pill status-${selectedReferral.status}`}>
                  {selectedReferral.status}
                </strong>
              </div>
              <div>
                <span>Urgency</span>
                <strong>{selectedReferral.urgency}</strong>
              </div>
              <div>
                <span>Submitted</span>
                <strong>{new Date(selectedReferral.created_at).toLocaleDateString()}</strong>
              </div>
            </div>

            <div className="modal-route">
              <span>{selectedReferral.referring_facility}</span>
              <i className="fa-solid fa-arrow-right" aria-hidden="true"></i>
              <span>{selectedReferral.receiving_facility}</span>
            </div>

            <div className="modal-detail-stack">
              {referralDetailGroups.map((group) => (
                <div key={group.title} className="modal-detail-group">
                  <h4>{group.title}</h4>
                  <div className="modal-field-grid">
                    {group.fields.map(([label, key]) => (
                      <div key={key} className="referral-detail-block">
                        <span>{label}</span>
                        <p>{selectedReferral[key] || 'Not provided'}</p>
                      </div>
                    ))}
                  </div>
                </div>
              ))}
              <div className="referral-detail-block">
                <span>Patient condition / clinical reason</span>
                <p>{selectedReferral.clinical_reason || 'Not provided'}</p>
              </div>
              <div className="referral-detail-block">
                <span>Clinical findings</span>
                <p>{selectedReferral.clinical_findings || 'Not provided'}</p>
              </div>
              <div className="referral-detail-block">
                <span>Requested services</span>
                <p>{selectedReferral.requested_services || 'Not provided'}</p>
              </div>
              <div className="referral-detail-block">
                <span>Receiving department</span>
                <p>{selectedReferral.receiving_department || 'Not provided'}</p>
              </div>
              <div className="referral-detail-block">
                <span>Assigned doctor</span>
                <p>{selectedReferral.assigned_doctor_name || 'Not assigned'}</p>
              </div>
              {summary?.user?.role === 'receptionist' && !selectedReferral.assigned_doctor_name && referralDoctors.length > 0 && (
                <div className="modal-detail-group" style={{ width: '100%' }}>
                  <h4>Assign Doctor</h4>
                  <div className="modal-field-grid">
                    <div className="referral-detail-block">
                      <span>Select doctor for this referral</span>
                      <select
                        value={selectedDoctorIdForAssignment}
                        onChange={(e) => setSelectedDoctorIdForAssignment(e.target.value)}
                        className="form-input"
                      >
                        <option value="">Choose doctor</option>
                        {referralDoctors.map((doctor) => (
                          <option key={doctor.id} value={doctor.id}>
                            {doctor.full_name} — {doctor.department_name}
                          </option>
                        ))}
                      </select>
                      <button
                        type="button"
                        className="button"
                        onClick={() => assignDoctorToReferral(selectedReferral.id)}
                        disabled={assigningDoctor}
                        style={{ marginTop: '0.75rem' }}
                      >
                        {assigningDoctor ? 'Assigning...' : 'Assign doctor'}
                      </button>
                      {assignmentMessage && (
                        <p className="dashboard-message" style={{ marginTop: '0.75rem' }}>
                          {assignmentMessage}
                        </p>
                      )}
                    </div>
                  </div>
                </div>
              )}
              <div className="modal-detail-group" style={{ width: '100%' }}>
                <h4>Referral communications</h4>
                <div className="modal-field-grid">
                  <div className="referral-detail-block" style={{ width: '100%' }}>
                    {loadingMessages ? (
                      <p>Loading messages...</p>
                    ) : messages.length === 0 ? (
                      <p>No messages yet for this referral.</p>
                    ) : (
                      <div className="message-list">
                        {messages.map((message) => (
                          <div key={message.id} className="message-card">
                            <div className="message-card-header">
                              <strong>{message.sender_name}</strong>
                              <span>{new Date(message.created_at).toLocaleString()}</span>
                            </div>
                            <p>{message.message}</p>
                          </div>
                        ))}
                      </div>
                    )}
                    {summary?.user?.role === 'receptionist' ? (
                      <form onSubmit={sendMessage} className="login-form" style={{ marginTop: '1rem' }}>
                        <div className="form-field">
                          <span>Send to</span>
                          <select
                            name="recipient_id"
                            value={messageForm.recipient_id}
                            onChange={(e) => setMessageForm((prev) => ({ ...prev, recipient_id: e.target.value }))}
                            className="form-input"
                            required
                          >
                            <option value="">Choose doctor</option>
                            {messageRecipients.map((recipient) => (
                              <option key={recipient.id} value={recipient.id}>
                                {recipient.label}
                              </option>
                            ))}
                          </select>
                        </div>
                        <div className="form-field">
                          <span>Message</span>
                          <textarea
                            name="message"
                            value={messageForm.message}
                            onChange={(e) => setMessageForm((prev) => ({ ...prev, message: e.target.value }))}
                            className="form-input"
                            rows="3"
                            required
                          />
                        </div>
                        {messageError && (
                          <p className="dashboard-message dashboard-error">{messageError}</p>
                        )}
                        <button type="submit" className="button" disabled={sendingMessage || messageRecipients.length === 0}>
                          {sendingMessage ? 'Sending...' : 'Send message'}
                        </button>
                      </form>
                    ) : (
                      <p className="dashboard-message">Only receptionists can send messages for this referral.</p>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </section>
        </div>
      )}
    </main>
  );
}
