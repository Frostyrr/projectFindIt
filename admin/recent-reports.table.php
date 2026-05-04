        <!-- Table card -->
        <div class="table-card">
            <div class="table-card-header">
                <h2>Recent Submissions</h2>
                <span>Latest 20 reports</span>
            </div>

            <?php if ($table_error): ?>
                <div class="alert alert-error" style="margin:18px 26px 0">
                    <i class="fas fa-circle-exclamation"></i>
                    <strong>Database error:</strong> <?= htmlspecialchars($table_error) ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item Name</th>
                            <th>Reported By</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_reports && $recent_reports->num_rows > 0): ?>
                            <?php while ($row = $recent_reports->fetch_assoc()): ?>
                            <tr id="row-<?= $row['id'] ?>">
                                <td><span class="item-id">#<?= $row['id'] ?></span></td>
                                <td class="td-item-name"><?= htmlspecialchars($row['item_name']) ?></td>
                                <td><?= htmlspecialchars($row['reporter_name'] ?? 'Unknown') ?></td>
                                <td><?= htmlspecialchars($row['location']) ?></td>
                                <td>
                                    <span class="badge <?= strtolower($row['type']) ?>">
                                        <?= ucfirst($row['type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= strtolower($row['status']) ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <div class="action-group">
                                        <!-- View -->
                                        <a href="../item_details.php?id=<?= $row['id'] ?>"
                                           class="btn-action" title="View item">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <!-- Edit — passes row data as JSON attributes -->
                                        <button type="button"
                                                class="btn-action btn-edit"
                                                title="Edit item"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['item_name'], ENT_QUOTES) ?>"
                                                data-desc="<?= htmlspecialchars($row['description'] ?? '', ENT_QUOTES) ?>"
                                                data-location="<?= htmlspecialchars($row['location'], ENT_QUOTES) ?>"
                                                data-type="<?= $row['type'] ?>"
                                                data-status="<?= $row['status'] ?>"
                                                data-date="<?= $row['date_lost_found'] ?? '' ?>"
                                                onclick="openEdit(this)">
                                            <i class="fas fa-pen"></i>
                                        </button>

                                        <!-- Delete -->
                                        <button type="button"
                                                class="btn-action btn-delete"
                                                title="Delete item"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['item_name'], ENT_QUOTES) ?>"
                                                onclick="openDelete(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="td-empty">No submissions found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>