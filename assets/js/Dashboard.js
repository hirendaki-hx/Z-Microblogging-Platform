// Dashboard.js - Complete File
// Character count for post textarea
function updateCharCount(textarea) {
    const maxLength = 280;
    const currentLength = textarea.value.length;
    const remaining = maxLength - currentLength;
    
    let charCount = textarea.parentElement.querySelector('.char-count');
    charCount.textContent = remaining;
    
    // Update button state
    const submitBtn = textarea.parentElement.querySelector('.submit-post-btn');
    if (currentLength > 0 && currentLength <= maxLength) {
        submitBtn.classList.add('active');
    } else {
        submitBtn.classList.remove('active');
    }
    
    // Color coding
    charCount.classList.remove('warning', 'error');
    if (remaining < 20) {
        charCount.classList.add('warning');
    }
    if (remaining < 0) {
        charCount.classList.add('error');
        submitBtn.classList.remove('active');
    }
}

// Image preview for main form
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const postForm = document.getElementById('post-form');
            let previewContainer = postForm.querySelector('.image-preview-container');
            
            if (!previewContainer) {
                previewContainer = document.createElement('div');
                previewContainer.className = 'image-preview-container';
                previewContainer.style.marginTop = '15px';
                
                const previewImage = document.createElement('img');
                previewImage.className = 'image-preview';
                previewImage.id = 'main-preview-image';
                
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'remove-image-btn';
                removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                removeBtn.onclick = function() {
                    previewContainer.remove();
                    input.value = '';
                };
                
                previewContainer.appendChild(previewImage);
                previewContainer.appendChild(removeBtn);
                postForm.querySelector('.post-actions').before(previewContainer);
            }
            
            previewContainer.querySelector('img').src = e.target.result;
            previewContainer.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Toggle like
function toggleLike(postId) {
    const likeBtn = document.querySelector(`.post-card[data-post-id="${postId}"] .like-btn`);
    const likeIcon = likeBtn.querySelector('i');
    const likeCount = document.getElementById(`like-count-${postId}`);
    
    const formData = new FormData();
    formData.append('post_id', postId);
    
    fetch('../assets/php/toggle_like.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            likeCount.textContent = data.like_count;
            if (data.liked) {
                likeIcon.classList.remove('far');
                likeIcon.classList.add('fas');
                likeBtn.classList.add('liked');
            } else {
                likeIcon.classList.remove('fas');
                likeIcon.classList.add('far');
                likeBtn.classList.remove('liked');
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

// Toggle follow
function toggleFollow(userId) {
    const followBtn = document.querySelector(`.user-to-follow[data-user-id="${userId}"] .follow-btn`);
    
    const formData = new FormData();
    formData.append('user_id', userId);
    
    fetch('../assets/php/toggle_follow.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.following) {
                followBtn.textContent = 'Following';
                followBtn.classList.add('following');
            } else {
                followBtn.textContent = 'Follow';
                followBtn.classList.remove('following');
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

// Open comments modal
let currentPostId = null;

function openComments(postId) {
    currentPostId = postId;
    const modal = document.getElementById('comment-modal');
    const content = document.getElementById('modal-content');
    
    // Show loading
    content.innerHTML = '<div class="spinner"></div>';
    modal.style.display = 'flex';
    
    // Load comments
    fetch(`../assets/php/get_comments.php?post_id=${postId}`)
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
            // Focus on comment textarea
            const textarea = content.querySelector('.add-comment textarea');
            if (textarea) {
                textarea.focus();
            }
        })
        .catch(error => {
            content.innerHTML = '<div class="empty-state">Error loading comments</div>';
        });
}

// Close comments modal
function closeComments() {
    document.getElementById('comment-modal').style.display = 'none';
}

// Add comment
function addComment(postId) {
    const textarea = document.querySelector(`#comment-form-${postId} textarea`);
    const comment = textarea.value.trim();
    
    if (!comment) return;
    
    const formData = new FormData();
    formData.append('post_id', postId);
    formData.append('comment', comment);
    
    fetch('../assets/php/add_comment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear textarea
            textarea.value = '';
            
            // Update comment count
            const commentCount = document.getElementById(`comment-count-${postId}`);
            commentCount.textContent = data.comment_count;
            
            // Reload comments
            openComments(postId);
        } else {
            alert('Error adding comment');
        }
    })
    .catch(error => console.error('Error:', error));
}

// ========== POST MENU FUNCTIONS ==========
let currentEditingPostId = null;
let postToDeleteId = null;
let currentPostImageUrl = null;
let editImageFile = null;

function togglePostMenu(postId, userId) {
    const dropdown = document.getElementById(`post-menu-${postId}`);
    const allDropdowns = document.querySelectorAll('.post-menu-dropdown');
    
    // Close all other dropdowns
    allDropdowns.forEach(d => {
        if (d.id !== `post-menu-${postId}`) {
            d.classList.remove('show');
        }
    });
    
    // Toggle current dropdown
    dropdown.classList.toggle('show');
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function closeDropdown(e) {
        if (!dropdown.contains(e.target) && !e.target.closest(`.post-menu[onclick*="${postId}"]`)) {
            dropdown.classList.remove('show');
            document.removeEventListener('click', closeDropdown);
        }
    });
}

// ========== TWEET MODAL FUNCTIONS ==========
let modalImageFile = null;

function openTweetModal() {
    document.getElementById('tweet-modal').style.display = 'flex';
    document.getElementById('modal-caption').focus();
}

function closeTweetModal() {
    document.getElementById('tweet-modal').style.display = 'none';
    resetTweetModal();
}

function resetTweetModal() {
    // Clear textarea
    document.getElementById('modal-caption').value = '';
    document.getElementById('modal-char-count').textContent = '280';
    document.getElementById('modal-char-count').classList.remove('warning', 'error');
    
    // Clear image preview
    document.getElementById('modal-image-preview').style.display = 'none';
    document.getElementById('modal-preview-image').src = '';
    modalImageFile = null;
    
    // Reset submit button
    const submitBtn = document.getElementById('modal-submit-post');
    submitBtn.classList.remove('active');
    submitBtn.style.opacity = '0.5';
    submitBtn.disabled = false;
    submitBtn.innerHTML = 'Tweet';
}

function updateModalCharCount(textarea) {
    const maxLength = 280;
    const currentLength = textarea.value.length;
    const remaining = maxLength - currentLength;
    
    const charCount = document.getElementById('modal-char-count');
    charCount.textContent = remaining;
    
    // Update button state
    const submitBtn = document.getElementById('modal-submit-post');
    if (currentLength > 0 && currentLength <= maxLength) {
        submitBtn.classList.add('active');
        submitBtn.style.opacity = '1';
    } else {
        submitBtn.classList.remove('active');
        submitBtn.style.opacity = '0.5';
    }
    
    // Color coding
    charCount.classList.remove('warning', 'error');
    if (remaining < 20) {
        charCount.classList.add('warning');
    }
    if (remaining < 0) {
        charCount.classList.add('error');
        submitBtn.classList.remove('active');
        submitBtn.style.opacity = '0.5';
    }
}

function previewModalImage(input) {
    if (input.files && input.files[0]) {
        modalImageFile = input.files[0];
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const previewContainer = document.getElementById('modal-image-preview');
            const previewImage = document.getElementById('modal-preview-image');
            
            previewImage.src = e.target.result;
            previewContainer.style.display = 'block';
            
            // Auto-scroll to show preview
            previewContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

function removeModalImage() {
    document.getElementById('modal-image-preview').style.display = 'none';
    document.getElementById('modal-preview-image').src = '';
    document.getElementById('modal-image-upload').value = '';
    modalImageFile = null;
}

function submitModalTweet() {
    const caption = document.getElementById('modal-caption').value.trim();
    const submitBtn = document.getElementById('modal-submit-post');
    
    if (!caption || caption.length > 280) {
        showNotification('Caption must be 1-280 characters', 'error');
        return;
    }
    
    // Create FormData
    const formData = new FormData();
    formData.append('caption', caption);
    formData.append('create_post', 'true');
    
    // Get image file from the modal
    const imageInput = document.getElementById('modal-image-upload');
    if (imageInput.files && imageInput.files[0]) {
        formData.append('image', imageInput.files[0]);
    }
    
    // Show loading state
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    submitBtn.disabled = true;
    
    // Submit via AJAX
    fetch('Dashboard.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            showNotification('Post created successfully!', 'success');
            closeTweetModal();
            // Refresh feed after 1 second
            setTimeout(loadPosts, 1000);
        } else {
            throw new Error('Server error: ' + response.status);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error creating post', 'error');
        if (submitBtn) {
            submitBtn.innerHTML = 'Tweet';
            submitBtn.disabled = false;
        }
    });
}

// ========== EDIT POST FUNCTIONS ==========
function editPost(postId) {
    currentEditingPostId = postId;
    
    // Close any open dropdowns
    document.querySelectorAll('.post-menu-dropdown').forEach(d => d.classList.remove('show'));
    
    // Fetch post data
    fetch(`../assets/php/get_post.php?post_id=${postId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Set caption
                document.getElementById('edit-caption').value = data.post.caption;
                updateEditCharCount(document.getElementById('edit-caption'));
                
                // Set current image if exists
                const currentImageContainer = document.getElementById('current-image-container');
                const currentImagePreview = document.getElementById('current-image-preview');
                
                if (data.post.image_url) {
                    currentPostImageUrl = data.post.image_url;
                    currentImagePreview.src = '../' + data.post.image_url;
                    currentImageContainer.style.display = 'block';
                } else {
                    currentPostImageUrl = null;
                    currentImageContainer.style.display = 'none';
                }
                
                // Clear new image preview
                document.getElementById('edit-image-preview').style.display = 'none';
                document.getElementById('edit-image-upload').value = '';
                editImageFile = null;
                
                // Show modal
                document.getElementById('edit-post-modal').style.display = 'flex';
            } else {
                alert('Error loading post data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading post data');
        });
}

function closeEditPostModal() {
    document.getElementById('edit-post-modal').style.display = 'none';
    resetEditModal();
}

function resetEditModal() {
    currentEditingPostId = null;
    currentPostImageUrl = null;
    editImageFile = null;
    
    document.getElementById('edit-caption').value = '';
    document.getElementById('edit-char-count').textContent = '280';
    document.getElementById('edit-char-count').classList.remove('warning', 'error');
    
    document.getElementById('current-image-container').style.display = 'none';
    document.getElementById('current-image-preview').src = '';
    
    document.getElementById('edit-image-preview').style.display = 'none';
    document.getElementById('edit-preview-image').src = '';
    
    const updateBtn = document.getElementById('update-post-btn');
    updateBtn.classList.remove('active');
    updateBtn.style.opacity = '0.5';
    updateBtn.disabled = false;
    updateBtn.innerHTML = 'Update';
}

function updateEditCharCount(textarea) {
    const maxLength = 280;
    const currentLength = textarea.value.length;
    const remaining = maxLength - currentLength;
    
    const charCount = document.getElementById('edit-char-count');
    charCount.textContent = remaining;
    
    // Update button state
    const updateBtn = document.getElementById('update-post-btn');
    if (currentLength > 0 && currentLength <= maxLength) {
        updateBtn.classList.add('active');
        updateBtn.style.opacity = '1';
    } else {
        updateBtn.classList.remove('active');
        updateBtn.style.opacity = '0.5';
    }
    
    // Color coding
    charCount.classList.remove('warning', 'error');
    if (remaining < 20) {
        charCount.classList.add('warning');
    }
    if (remaining < 0) {
        charCount.classList.add('error');
        updateBtn.classList.remove('active');
        updateBtn.style.opacity = '0.5';
    }
}

function removeCurrentImage() {
    currentPostImageUrl = null;
    document.getElementById('current-image-container').style.display = 'none';
}

function previewEditImage(input) {
    if (input.files && input.files[0]) {
        editImageFile = input.files[0];
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const previewContainer = document.getElementById('edit-image-preview');
            const previewImage = document.getElementById('edit-preview-image');
            
            previewImage.src = e.target.result;
            previewContainer.style.display = 'block';
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

function removeEditImage() {
    document.getElementById('edit-image-preview').style.display = 'none';
    document.getElementById('edit-preview-image').src = '';
    document.getElementById('edit-image-upload').value = '';
    editImageFile = null;
}

function updatePost() {
    if (!currentEditingPostId) return;
    
    const caption = document.getElementById('edit-caption').value.trim();
    const updateBtn = document.getElementById('update-post-btn');
    
    if (!updateBtn.classList.contains('active')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('post_id', currentEditingPostId);
    formData.append('caption', caption);
    formData.append('update_post', 'true');
    
    // Handle image logic
    if (currentPostImageUrl === null) {
        formData.append('remove_image', 'true');
    }
    
    if (editImageFile) {
        formData.append('image', editImageFile);
        formData.append('replace_image', 'true');
    }
    
    // Show loading state
    updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    updateBtn.disabled = true;
    
    fetch('../assets/php/update_post.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeEditPostModal();
            window.location.reload();
        } else {
            alert(data.error || 'Error updating post');
            updateBtn.innerHTML = 'Update';
            updateBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating post');
        updateBtn.innerHTML = 'Update';
        updateBtn.disabled = false;
    });
}

// ========== DELETE POST FUNCTIONS ==========
function deletePost(postId) {
    postToDeleteId = postId;
    document.getElementById('delete-confirm-modal').style.display = 'flex';
}

function confirmDeletePost() {
    if (currentEditingPostId) {
        postToDeleteId = currentEditingPostId;
        closeEditPostModal();
        document.getElementById('delete-confirm-modal').style.display = 'flex';
    }
}

function cancelDelete() {
    document.getElementById('delete-confirm-modal').style.display = 'none';
    postToDeleteId = null;
}

function performDelete() {
    if (!postToDeleteId) return;
    
    const deleteBtn = document.getElementById('confirm-delete-btn');
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    deleteBtn.disabled = true;
    
    const formData = new FormData();
    formData.append('post_id', postToDeleteId);
    formData.append('delete_post', 'true');
    
    fetch('../assets/php/delete_post.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove post from DOM
            const postElement = document.querySelector(`.post-card[data-post-id="${postToDeleteId}"]`);
            if (postElement) {
                postElement.style.opacity = '0';
                postElement.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    postElement.remove();
                }, 300);
            }
            
            document.getElementById('delete-confirm-modal').style.display = 'none';
            postToDeleteId = null;
        } else {
            alert(data.error || 'Error deleting post');
            deleteBtn.innerHTML = 'Delete';
            deleteBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting post');
        deleteBtn.innerHTML = 'Delete';
        deleteBtn.disabled = false;
    });
}

// ========== OTHER POST MENU FUNCTIONS ==========
function reportPost(postId) {
    const reason = prompt('Please enter reason for reporting this post:');
    if (reason) {
        alert('Post reported. Thank you for your feedback.');
    }
}

function muteUser(userId) {
    if (confirm('Mute this user? You won\'t see their posts or notifications from them.')) {
        alert('User muted successfully.');
    }
}

function blockUser(userId) {
    if (confirm('Block this user? They won\'t be able to follow you or see your posts, and you won\'t see their posts.')) {
        alert('User blocked successfully.');
    }
}

// ========== IMAGE PREVIEW FUNCTIONS ==========
function openImagePreview(imageSrc) {
    const fullSizeImage = document.getElementById('full-size-image');
    fullSizeImage.src = imageSrc;
    document.getElementById('image-preview-modal').style.display = 'flex';
}

function closeImagePreview() {
    document.getElementById('image-preview-modal').style.display = 'none';
}

// ========== UTILITY FUNCTIONS ==========
function openPostModal() {
    if (window.innerWidth <= 680) {
        openTweetModal();
    } else {
        const mainTextarea = document.querySelector('.create-post textarea');
        if (mainTextarea) {
            mainTextarea.focus();
        }
    }
}

function closePostModal() {
    document.getElementById('post-modal').style.display = 'none';
}

function initSearch() {
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query) {
                    console.log('Searching for:', query);
                }
            }
        });
    }
}

function initProfilePics() {
    document.querySelectorAll('.profile-pic, .profile-pic-large, .profile-pic-sm, .profile-pic-md').forEach(pic => {
        if (!pic.hasAttribute('data-letter')) {
            let text = pic.textContent.trim();
            if (text) {
                const firstLetter = text.charAt(0).toUpperCase();
                pic.setAttribute('data-letter', firstLetter);
                pic.textContent = firstLetter;
            }
        }
    });
}

function initFormHandlers() {
    // Main post form
    const postForm = document.getElementById('post-form');
    if (postForm) {
        postForm.addEventListener('submit', function(e) {
            const textarea = this.querySelector('textarea');
            if (!textarea.value.trim() || textarea.value.length > 280) {
                e.preventDefault();
                alert('Please enter a valid caption (1-280 characters)');
            }
        });
    }
    
    // Mobile post form
    const mobilePostForm = document.getElementById('mobile-post-form');
    if (mobilePostForm) {
        mobilePostForm.addEventListener('submit', function(e) {
            const textarea = this.querySelector('textarea');
            if (!textarea.value.trim() || textarea.value.length > 280) {
                e.preventDefault();
                alert('Please enter a valid caption (1-280 characters)');
            }
        });
    }
}

function initModalClickHandlers() {
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                if (this.id === 'tweet-modal') {
                    closeTweetModal();
                } else if (this.id === 'image-preview-modal') {
                    closeImagePreview();
                } else if (this.id === 'comment-modal') {
                    closeComments();
                } else if (this.id === 'edit-post-modal') {
                    closeEditPostModal();
                } else if (this.id === 'delete-confirm-modal') {
                    cancelDelete();
                }
            }
        });
    });
}

function initKeyboardHandlers() {
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (document.getElementById('tweet-modal').style.display === 'flex') {
                closeTweetModal();
            }
            if (document.getElementById('image-preview-modal').style.display === 'flex') {
                closeImagePreview();
            }
            if (document.getElementById('comment-modal').style.display === 'flex') {
                closeComments();
            }
            if (document.getElementById('edit-post-modal').style.display === 'flex') {
                closeEditPostModal();
            }
            if (document.getElementById('delete-confirm-modal').style.display === 'flex') {
                cancelDelete();
            }
        }
    });
}

function initTweetButtons() {
    const postBtn = document.querySelector('.post-btn');
    if (postBtn) {
        postBtn.addEventListener('click', openTweetModal);
    }
    
    const mobilePostBtn = document.querySelector('.mobile-post-btn');
    if (mobilePostBtn) {
        mobilePostBtn.addEventListener('click', openTweetModal);
    }
}

// ========== MAIN INITIALIZATION ==========
document.addEventListener('DOMContentLoaded', function() {
    initProfilePics();
    initSearch();
    initFormHandlers();
    initModalClickHandlers();
    initKeyboardHandlers();
    initTweetButtons();
    
    // Close all dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.post-menu-container')) {
            document.querySelectorAll('.post-menu-dropdown').forEach(d => d.classList.remove('show'));
        }
    });
    
    console.log('Dashboard initialized');
});