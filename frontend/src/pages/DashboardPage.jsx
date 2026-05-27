import { useState, useEffect } from 'react';
import { dashboardApi } from '../api';
import {
  Package, Archive, MapPin, Users, AlertTriangle,
  TrendingDown, Clock, CheckCircle
} from 'lucide-react';

export default function DashboardPage() {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    dashboardApi.getStats()
      .then(res => setData(res.data))
      .catch(console.error)
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <div className="loading-spinner"><div className="spinner"></div></div>;

  const stats = data?.stats || {};

  const statCards = [
    { label: 'Total Items', value: stats.total_items, icon: Package, color: '#6366f1' },
    { label: 'Cupboards', value: stats.total_cupboards, icon: Archive, color: '#8b5cf6' },
    { label: 'Places', value: stats.total_places, icon: MapPin, color: '#a855f7' },
    { label: 'Users', value: stats.total_users, icon: Users, color: '#06b6d4' },
    { label: 'In Store', value: stats.items_in_store, icon: CheckCircle, color: '#10b981' },
    { label: 'Borrowed', value: stats.items_borrowed, icon: Clock, color: '#f59e0b' },
    { label: 'Damaged', value: stats.items_damaged, icon: AlertTriangle, color: '#ef4444' },
    { label: 'Missing', value: stats.items_missing, icon: TrendingDown, color: '#64748b' },
  ];

  const formatAction = (log) => {
    const modelName = log.auditable_type?.split('\\').pop() || 'Record';
    return `${log.action.replace('_', ' ')} ${modelName}`;
  };

  const timeAgo = (date) => {
    const diff = Date.now() - new Date(date).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'Just now';
    if (mins < 60) return `${mins}m ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return `${hrs}h ago`;
    return `${Math.floor(hrs / 24)}d ago`;
  };

  return (
    <>
      <div className="page-header">
        <h2>Dashboard</h2>
        <p>Overview of your inventory management system</p>
      </div>
      <div className="page-body">
        <div className="stats-grid">
          {statCards.map((stat) => (
            <div className="stat-card" key={stat.label}>
              <div className="stat-icon" style={{ background: `${stat.color}15` }}>
                <stat.icon size={20} style={{ color: stat.color }} />
              </div>
              <div className="stat-value">{stat.value || 0}</div>
              <div className="stat-label">{stat.label}</div>
            </div>
          ))}
        </div>

        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' }}>
          <div className="card">
            <div className="card-header">
              <h3 style={{ fontSize: '15px', fontWeight: 600 }}>Recent Activity</h3>
            </div>
            <div className="card-body">
              {data?.recent_activity?.length > 0 ? (
                data.recent_activity.map((log) => (
                  <div className="activity-item" key={log.id}>
                    <div className={`activity-dot ${log.action}`}></div>
                    <div className="activity-content">
                      <div className="activity-text">
                        <strong>{log.user?.name || 'System'}</strong> {formatAction(log)}
                      </div>
                      <div className="activity-time">{timeAgo(log.created_at)}</div>
                    </div>
                  </div>
                ))
              ) : (
                <div className="empty-state"><p>No recent activity</p></div>
              )}
            </div>
          </div>

          <div className="card">
            <div className="card-header">
              <h3 style={{ fontSize: '15px', fontWeight: 600 }}>Active Borrowings</h3>
              {stats.overdue_borrowings > 0 && (
                <span className="badge badge-overdue">
                  {stats.overdue_borrowings} overdue
                </span>
              )}
            </div>
            <div className="card-body">
              {data?.recent_borrowings?.length > 0 ? (
                data.recent_borrowings.map((b) => (
                  <div className="activity-item" key={b.id}>
                    <div className="activity-dot borrowed"></div>
                    <div className="activity-content">
                      <div className="activity-text">
                        <strong>{b.borrower_name}</strong> borrowed{' '}
                        <strong>{b.item?.name}</strong> (x{b.quantity_borrowed})
                      </div>
                      <div className="activity-time">
                        Due: {new Date(b.expected_return_date).toLocaleDateString()}
                      </div>
                    </div>
                  </div>
                ))
              ) : (
                <div className="empty-state"><p>No active borrowings</p></div>
              )}
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
