import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import {
  LayoutDashboard, Package, Archive, MapPin,
  Users, ClipboardList, ScrollText, LogOut, Box
} from 'lucide-react';

export default function Layout() {
  const { user, logout, isAdmin } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  const navItems = [
    { section: 'Overview', items: [
      { to: '/', icon: LayoutDashboard, label: 'Dashboard' },
    ]},
    { section: 'Inventory', items: [
      { to: '/cupboards', icon: Archive, label: 'Cupboards' },
      { to: '/places', icon: MapPin, label: 'Places' },
      { to: '/items', icon: Package, label: 'Items' },
    ]},
    { section: 'Operations', items: [
      { to: '/borrowings', icon: ClipboardList, label: 'Borrowings' },
      { to: '/audit-logs', icon: ScrollText, label: 'Audit Logs' },
    ]},
  ];

  if (isAdmin) {
    navItems.push({
      section: 'Administration',
      items: [
        { to: '/users', icon: Users, label: 'Users' },
      ],
    });
  }

  return (
    <div className="app-layout">
      <aside className="sidebar">
        <div className="sidebar-brand">
          <div className="brand-icon"><Box size={22} /></div>
          <div>
            <h1>Inventory IMS</h1>
            <p>Ceyntics Systems</p>
          </div>
        </div>

        <nav className="sidebar-nav">
          {navItems.map((section) => (
            <div className="nav-section" key={section.section}>
              <div className="nav-section-title">{section.section}</div>
              {section.items.map((item) => (
                <NavLink
                  key={item.to}
                  to={item.to}
                  end={item.to === '/'}
                  className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}
                >
                  <item.icon />
                  {item.label}
                </NavLink>
              ))}
            </div>
          ))}
        </nav>

        <div className="sidebar-footer">
          <div className="user-info">
            <div className="user-avatar">
              {user?.name?.charAt(0)?.toUpperCase()}
            </div>
            <div className="user-details">
              <div className="name">{user?.name}</div>
              <div className="role">{user?.role}</div>
            </div>
          </div>
          <button className="logout-btn" onClick={handleLogout}>
            <LogOut size={14} /> Sign Out
          </button>
        </div>
      </aside>

      <main className="main-content">
        <Outlet />
      </main>
    </div>
  );
}
