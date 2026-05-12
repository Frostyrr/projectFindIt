<div class="reports-grid">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="report-card" onclick="goToDetails(<?= $row['id'] ?>)">

                <div class="card-image-container">
                    <?php if (!empty($row['image_path'])): ?>
                        <img src="<?= htmlspecialchars($row['image_path']) ?>" class="card-image" alt="Item Image">
                    <?php else: ?>
                        <div class="card-image placeholder-image">No Image Available</div>
                    <?php endif; ?>
                                
                    <div class="card-badges">
                        <?php if (strtolower($row['status']) === 'found'): ?>
                            <span class="badge status-badge found" style="background-color: #3d7a54;">Found</span>
                        <?php else: ?>
                            <span class="badge status-badge <?= strtolower($row['type']) ?>"
                                    style="background-color: <?= strtolower($row['type']) === 'lost' ? '#d9534f' : '#3d7a54' ?>;">
                                <?= ucfirst(htmlspecialchars($row['type'])) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                            
                <div class="card-content">
                    <div class="card-meta">
                        <span class="meta-item">📅 <?= date('M d, Y', strtotime($row['date_lost_found'] ?? $row['created_at'])) ?></span>
                    </div>
                    
                    <h3 class="card-title"><?= htmlspecialchars($row['item_name']) ?></h3>
                    <p class="card-desc"><?= htmlspecialchars($row['description']) ?></p>
                                
                    <hr class="card-divider">
                                
                    <div class="card-footer">
                        <span class="card-location">📍 <?= htmlspecialchars($row['location']) ?></span>
                        <a href="item_details.php?id=<?= $row['id'] ?>" class="view-details-btn">VIEW DETAILS</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No recent lost items reported.</p>
    <?php endif; ?>
</div>

<script src="js/goToDetails.js"></script>