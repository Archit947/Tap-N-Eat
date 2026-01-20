import './App.css'
import { HashRouter, Routes, Route, Navigate } from 'react-router-dom';
import AdminDashboard from './components/AdminDashboard';
import VisitorOrder from './components/VisitorOrder';
import AdminLogin from './components/AdminLogin';

function App() {
  return (
    <div className="app">
      <HashRouter>
        <Routes>
          <Route path="/" element={<AdminLogin />} />
          <Route path="/visitor" element={<VisitorOrder />} />
          <Route path="/admin-login" element={<AdminLogin />} />
          <Route path="/admin" element={<AdminDashboard />} />
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </HashRouter>
    </div>
  )
}

export default App
