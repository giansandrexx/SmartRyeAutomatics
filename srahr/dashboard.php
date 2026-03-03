<?php
session_start();
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../config.php");
    exit();
}

$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : $_SESSION['username'];
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SRA Scheduling</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../sratool/img/favicon-32x32.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>

<div class="top-header">
    <div class="logo-section">
        <img src="https://smartrye.com.ph/ams/public/backend/images/logo-sra.png" alt="Logo" class="logo-img">
        <h1 class="system-title">SRA Scheduling</h1>
    </div>
    <div class="header-right">
        <div class="current-date"><?php echo date('l, jS F Y'); ?></div>
        <div class="user-info">
            <div class="user-icon"><i class="fas fa-user"></i></div>
            <div>
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($_SESSION['role']); ?></div>
            </div>
            <div class="user-dropdown-wrap">
                <button class="user-dropdown-toggle" id="userDropdownBtn">
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="user-dropdown-menu" id="userDropdownMenu">
                    <a href="../portal" class="dropdown-item">
                        <i class="fas fa-arrow-left"></i> Back to Portal
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="../sratool/logout" class="dropdown-item dropdown-item-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-layout">

    <aside class="sidebar-panel">
        <div id="upcomingWidget" class="upcoming-widget"></div>
    </aside>

    <div class="main-panel">

        <div id="searchFilterBar" class="search-filter-bar"></div>

        <div class="calendar-controls">
            <div class="cal-nav">
                <button class="nav-btn" id="prevBtn"><i class="fas fa-chevron-left"></i></button>
                <div class="cal-title-wrap">
                    <span class="cal-month" id="calMonth">February</span>
                    <span class="cal-year"  id="calYear">2026</span>
                </div>
                <button class="nav-btn" id="nextBtn"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div class="cal-actions">
                <button class="btn-today" id="todayBtn"><i class="fas fa-calendar-day"></i> Today</button>
                <div class="view-toggle">
                    <button class="view-btn active" id="monthViewBtn">Month</button>
                    <button class="view-btn"         id="yearViewBtn">Year</button>
                </div>
                <?php if ($user_role === 'admin' || $user_role === 'moderator'): ?>
                <button class="btn-add-event" id="addEventBtn">
                    <i class="fas fa-plus"></i> Add Event
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="calendar-wrap" id="monthView">
            <div class="calendar-grid">
                <div class="day-header">Sun</div>
                <div class="day-header">Mon</div>
                <div class="day-header">Tue</div>
                <div class="day-header">Wed</div>
                <div class="day-header">Thu</div>
                <div class="day-header">Fri</div>
                <div class="day-header">Sat</div>
            </div>
            <div class="calendar-days" id="calendarDays"></div>
        </div>

        <div class="year-view" id="yearView" style="display:none;">
            <div class="year-grid" id="yearGrid"></div>
        </div>

    </div>
</div>

<div class="modal-overlay" id="dayModal">
    <div class="modal-box">
        <div class="modal-head">
            <div>
                <div class="modal-date" id="modalDate"></div>
                <div class="modal-day"  id="modalDay"></div>
            </div>
            <button class="modal-close" id="modalClose"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body-content">
            <div class="events-section">
                <div class="events-header">
                    <h4><i class="fas fa-calendar-check"></i> Scheduled Events</h4>
                    <?php if ($user_role === 'admin' || $user_role === 'moderator'): ?>
                    <button class="btn-add-small" id="addFromModal"><i class="fas fa-plus"></i> Add</button>
                    <?php endif; ?>
                </div>
                <div class="events-list" id="eventsList"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="eventModal">
    <div class="modal-box modal-box-sm">
        <div class="modal-head">
            <h3 id="eventModalTitle">Add Event</h3>
            <button class="modal-close" id="eventModalClose"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body-content">
            <div class="form-group">
                <label>Title *</label>
                <input type="text" id="eventTitle" placeholder="Event title…" class="form-input">
            </div>
            <div class="form-group">
                <label>Date *</label>
                <input type="date" id="eventDate" class="form-input">
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label>Start Time</label>
                    <input type="time" id="eventStart" class="form-input">
                </div>
                <div class="form-group">
                    <label>End Time</label>
                    <input type="time" id="eventEnd" class="form-input">
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label>Category</label>
                    <select id="eventCategory" class="form-input">
                        <option value="meeting">Meeting</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="training">Training</option>
                        <option value="inspection">Inspection</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="eventStatus" class="form-input">
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Assigned To</label>
                <input type="text" id="eventAssignee" placeholder="Person or team…" class="form-input">
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea id="eventNotes" placeholder="Additional notes…" class="form-input" rows="3"></textarea>
            </div>
            <div class="modal-footer-btns">
                <button class="btn-cancel-modal" id="cancelEvent">Cancel</button>
                <button class="btn-save-event"   id="saveEvent"><i class="fas fa-save"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="eventDetailModal">
    <div class="modal-box modal-box-sm">
        <div class="modal-head" id="detailHeader">
            <div>
                <div class="modal-date" id="detailTitle"></div>
                <span id="detailCategory" class="event-category-tag"></span>
            </div>
            <button class="modal-close" id="detailModalClose"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body-content">
            <div class="detail-row" id="detailTimeRow">
                <div class="detail-icon"><i class="fas fa-clock"></i></div>
                <div class="detail-content">
                    <div class="detail-label">Time</div>
                    <div class="detail-value" id="detailTime"></div>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-icon"><i class="fas fa-calendar"></i></div>
                <div class="detail-content">
                    <div class="detail-label">Date</div>
                    <div class="detail-value" id="detailDate"></div>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-icon"><i class="fas fa-flag"></i></div>
                <div class="detail-content">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <?php if ($user_role === 'admin' || $user_role === 'moderator'): ?>
                        <select id="detailStatusSelect" class="form-input" style="padding:4px 8px;font-size:13px;width:auto;">
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <?php else: ?>
                        <span id="detailStatusText"></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="detail-row" id="detailAssigneeRow">
                <div class="detail-icon"><i class="fas fa-user"></i></div>
                <div class="detail-content">
                    <div class="detail-label">Assigned To</div>
                    <div class="detail-value" id="detailAssignee"></div>
                </div>
            </div>
            <div class="detail-row" id="detailNotesRow">
                <div class="detail-icon"><i class="fas fa-sticky-note"></i></div>
                <div class="detail-content">
                    <div class="detail-label">Notes</div>
                    <div class="detail-value" id="detailNotes"></div>
                </div>
            </div>
            <div style="margin-top:18px;">
                <div style="font-size:13px;font-weight:600;color:#8899aa;letter-spacing:.5px;margin-bottom:10px;">
                    <i class="fas fa-history"></i> ACTIVITY LOG
                </div>
                <div id="activityLog" style="max-height:180px;overflow-y:auto;padding-right:4px;"></div>
                <?php if ($user_role === 'admin' || $user_role === 'moderator'): ?>
                <div style="display:flex;gap:8px;margin-top:12px;">
                    <input type="text" id="commentInput" placeholder="Add a comment…" class="form-input" style="flex:1;padding:8px 12px;font-size:13px;">
                    <button id="submitComment" class="btn-save-event" style="padding:8px 14px;white-space:nowrap;">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer-btns" style="margin-top:20px;">
                <button class="btn-cancel-modal" id="detailDeleteBtn" style="border-color:#ef9a9a;color:#e53935;">
                    <i class="fas fa-trash"></i> Delete
                </button>
                <button class="btn-save-event" id="detailEditBtn">
                    <i class="fas fa-edit"></i> Edit Event
                </button>
            </div>
        </div>
    </div>
</div>

<div class="delete-confirm-overlay" id="deleteConfirmModal">
    <div class="delete-confirm-box">
        <div class="delete-confirm-top">
            <div class="delete-confirm-icon">
                <i class="fas fa-trash-alt"></i>
            </div>
            <h3>Delete Event</h3>
        </div>
        <div class="delete-confirm-body">
            <p>You are about to permanently delete:</p>
            <div class="delete-event-name" id="deleteEventName">Event Title</div>
            <div class="delete-confirm-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <span>This action cannot be undone. All event data and activity logs will be removed.</span>
            </div>
        </div>
        <div class="delete-confirm-actions">
            <button class="btn-delete-cancel" id="deleteCancelBtn">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button class="btn-delete-confirm" id="deleteConfirmBtn">
                <i class="fas fa-trash-alt"></i> Delete
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/dashboard.js"></script>
<script src="../js/dropdown.js"></script>
</body>
</html>