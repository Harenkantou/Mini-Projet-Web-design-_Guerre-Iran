<?php
// _sidebar.php — inclus dans chaque page admin
// Définir $activePage avant l'include : 'articles-new' | 'categories' | 'articles-list' | 'front'
$activePage  = $activePage ?? '';
$userEmail   = $_SESSION['user']['email'] ?? 'admin';
$userInitial = strtoupper(substr($userEmail, 0, 1));
?>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#f0f2f5;color:#1a1f2e;min-height:100vh;}

.bo-sidebar{
  position:fixed;top:0;left:0;
  width:220px;height:100vh;
  background:#1e2330;
  display:flex;flex-direction:column;
  z-index:200;overflow:hidden;
}
.bo-brand{
  padding:20px 18px 16px;
  border-bottom:1px solid #2d3347;
  display:flex;align-items:center;gap:10px;flex-shrink:0;
}
.bo-brand-icon{
  width:32px;height:32px;min-width:32px;min-height:32px;
  background:#4f7ef8;border-radius:8px;
  display:flex;align-items:center;justify-content:center;
}
.bo-brand-icon svg{width:16px!important;height:16px!important;display:block;flex-shrink:0;}
.bo-brand-name{font-size:13px;font-weight:700;color:#fff;line-height:1.2;}
.bo-brand-sub{font-size:11px;color:#9ba8c0;}

.bo-nav{
  flex:1;padding:12px 8px;
  display:flex;flex-direction:column;gap:2px;overflow-y:auto;
}
.bo-section-label{
  font-size:10px;font-weight:700;letter-spacing:.8px;
  text-transform:uppercase;color:#3a4258;padding:8px 10px 6px;
}
.bo-link{
  display:flex;align-items:center;gap:9px;
  padding:9px 11px;border-radius:8px;
  text-decoration:none;color:#8a98b4;
  font-size:13px;font-weight:500;
  transition:background .15s,color .15s;
  line-height:1;
}
.bo-link:hover{background:#252c42;color:#c8d2e8;}
.bo-link.active{background:#2c3550;color:#fff;}
.bo-link svg{
  width:15px!important;height:15px!important;
  min-width:15px;min-height:15px;
  flex-shrink:0;opacity:.65;display:block;
}
.bo-link:hover svg,.bo-link.active svg{opacity:1;color:#4f7ef8;}

.bo-footer{padding:10px 8px;border-top:1px solid #2d3347;flex-shrink:0;}
.bo-user{display:flex;align-items:center;gap:9px;padding:8px 10px;}
.bo-avatar{
  width:28px;height:28px;min-width:28px;min-height:28px;
  background:#4f7ef8;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:12px;font-weight:700;color:#fff;flex-shrink:0;
}
.bo-user-name{font-size:12px;font-weight:600;color:#c8d2e8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.bo-user-role{font-size:11px;color:#3a4258;}
.bo-logout{
  display:flex;align-items:center;gap:9px;
  padding:9px 11px;border-radius:8px;
  color:#5a6882;font-size:13px;font-weight:500;
  text-decoration:none;transition:background .15s,color .15s;
}
.bo-logout:hover{background:rgba(239,68,68,.1);color:#f87171;}
.bo-logout svg{
  width:15px!important;height:15px!important;
  min-width:15px;min-height:15px;
  flex-shrink:0;display:block;
}

/* ── MAIN WRAP ── */
.wrap{margin-left:220px;min-height:100vh;padding:28px 28px 48px;}

/* ── PAGE HEADER ── */
.page-head{margin-bottom:22px;}
.page-head h1{font-size:22px;font-weight:700;letter-spacing:-.3px;margin:0;}
.page-head .muted{margin-top:3px;}

/* ── CARD ── */
.card{
  background:#fff;border-radius:12px;
  box-shadow:0 1px 3px rgba(0,0,0,.07),0 4px 16px rgba(0,0,0,.05);
  padding:22px 24px;margin-bottom:18px;border:1px solid #e4e8f0;
}
.card-title{
  font-size:14px;font-weight:700;color:#1a1f2e;
  margin:0 0 18px;padding-bottom:14px;
  border-bottom:1px solid #e4e8f0;
  display:flex;align-items:center;gap:8px;
}

/* ── FORM ── */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.form-grid .full{grid-column:1/-1;}
label{display:block;font-size:12.5px;font-weight:600;color:#4a5574;margin-bottom:6px;}
input[type=text],input[type=date],input[type=file],input[type=email],
input[type=password],textarea,select{
  width:100%;padding:9px 12px;border:1px solid #e4e8f0;border-radius:8px;
  font:inherit;font-size:13.5px;color:#1a1f2e;background:#fafbfc;
  transition:border-color .15s,box-shadow .15s;outline:none;box-sizing:border-box;
}
input:focus,textarea:focus,select:focus{
  border-color:#4f7ef8;box-shadow:0 0 0 3px rgba(79,126,248,.12);background:#fff;
}
textarea{min-height:120px;resize:vertical;}

/* ── BUTTONS ── */
.btn{
  display:inline-flex;align-items:center;gap:6px;
  padding:9px 16px;border-radius:8px;
  font:inherit;font-size:13px;font-weight:600;cursor:pointer;
  transition:all .15s;border:1px solid #e4e8f0;
  background:#fff;color:#1a1f2e;text-decoration:none;
}
.btn:hover{background:#f5f6f8;}
.btn.primary{background:#4f7ef8;color:#fff;border-color:#4f7ef8;}
.btn.primary:hover{background:#3b6ae0;border-color:#3b6ae0;}
.btn.danger{background:#fff;color:#dc2626;border-color:#fca5a5;}
.btn.danger:hover{background:#fef2f2;}
.actions{
  display:flex;gap:10px;flex-wrap:wrap;align-items:center;
  margin-top:18px;padding-top:16px;border-top:1px solid #e4e8f0;
}

/* ── ALERTS ── */
.success{padding:10px 14px;background:#edfaf3;border:1px solid #a3e4bf;color:#1a6640;border-radius:8px;font-size:13px;font-weight:500;margin-bottom:16px;}
.error{padding:10px 14px;background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;border-radius:8px;font-size:13px;font-weight:500;margin-bottom:16px;}
.muted{color:#6b7590;font-size:13px;}

/* ── ARTICLE LIST ── */
.article-list{margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:10px;}
.article-item{
  display:flex;align-items:flex-start;gap:14px;
  padding:14px 16px;border:1px solid #e4e8f0;border-radius:10px;
  background:#fafbfc;transition:border-color .15s,box-shadow .15s;
}
.article-item:hover{border-color:#c8d3e8;box-shadow:0 2px 8px rgba(0,0,0,.06);}
.article-thumb-wrap{flex-shrink:0;}
.article-thumb{display:block;width:80px;height:56px;object-fit:cover;border-radius:8px;border:1px solid #e4e8f0;}
.compact-meta{margin-top:4px;font-size:11px;color:#6b7590;}
.article-info{flex:1;min-width:0;}
.article-title{font-size:14px;font-weight:600;color:#1a1f2e;margin-bottom:5px;line-height:1.3;}
.article-title h1,.article-title h2,.article-title h3,
.article-title h4,.article-title h5,.article-title h6,
.article-title p{margin:0;line-height:1.3;}
.article-title h1{font-size:16px;}.article-title h2{font-size:15px;}.article-title h3{font-size:14px;}
.article-meta-row{font-size:12px;color:#6b7590;display:flex;gap:12px;flex-wrap:wrap;margin-bottom:10px;}
.article-actions-col{display:flex;gap:8px;align-items:center;flex-shrink:0;}

/* ── CATEGORY TABLE ── */
.table-wrap{overflow-x:auto;}
.category-table{width:100%;border-collapse:collapse;font-size:13px;min-width:600px;}
.category-table thead th{
  background:#f5f7fb;padding:10px 14px;text-align:left;
  font-size:11.5px;font-weight:700;text-transform:uppercase;
  letter-spacing:.5px;color:#5f6e8a;border-bottom:1px solid #e4e8f0;
}
.category-table tbody td{padding:11px 14px;border-bottom:1px solid #f0f3f8;vertical-align:middle;}
.category-table tbody tr:last-child td{border-bottom:none;}
.category-table tbody tr:hover td{background:#fafbfd;}

/* ── CATEGORY CHECKBOXES ── */
.category-check-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(175px,1fr));gap:8px;}
.category-check-item{
  display:flex;align-items:center;gap:8px;padding:8px 10px;
  border:1px solid #e4e8f0;border-radius:8px;background:#fafbfc;
  font-size:13px;cursor:pointer;transition:border-color .15s;
}
.category-check-item:hover{border-color:#4f7ef8;}
.category-check-item input{width:auto;flex-shrink:0;}

/* ── IMAGE GALLERY ── */
.image-gallery{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;margin-top:8px;}
.image-item{border:1px solid #e4e8f0;border-radius:9px;overflow:hidden;background:#f5f7fb;}
.image-item img{width:100%;height:100px;object-fit:cover;display:block;}
.image-item .compact-meta{padding:5px 8px;font-size:11px;}

/* ── BADGE ── */
.badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;background:#eef2fe;color:#4f7ef8;}

::-webkit-scrollbar{width:5px;height:5px;}
::-webkit-scrollbar-track{background:transparent;}
::-webkit-scrollbar-thumb{background:#d0d5e0;border-radius:99px;}

@media(max-width:760px){
  .bo-sidebar{transform:translateX(-100%);}
  .wrap{margin-left:0;}
  .form-grid{grid-template-columns:1fr;}
}
</style>

<aside class="bo-sidebar">
  <div class="bo-brand">
    <div class="bo-brand-icon">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="2" width="5" height="5" rx="1"/>
        <rect x="9" y="2" width="5" height="5" rx="1"/>
        <rect x="2" y="9" width="5" height="5" rx="1"/>
        <rect x="9" y="9" width="5" height="5" rx="1"/>
      </svg>
    </div>
    <div>
      <div class="bo-brand-name">Back Office</div>
      <div class="bo-brand-sub">Administration</div>
    </div>
  </div>

  <nav class="bo-nav">
    <div class="bo-section-label">Sections</div>

    <a class="bo-link <?= $activePage === 'articles-new'  ? 'active' : '' ?>" href="/admin/articles/new">
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="8" cy="8" r="6"/>
        <line x1="8" y1="5" x2="8" y2="11"/>
        <line x1="5" y1="8" x2="11" y2="8"/>
      </svg>
      Ajouter un article
    </a>

    <a class="bo-link <?= $activePage === 'categories'    ? 'active' : '' ?>" href="/admin/categories">
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M2 4h12M2 8h8M2 12h5"/>
      </svg>
      Gestion catégorie
    </a>

    <a class="bo-link <?= $activePage === 'articles-list' ? 'active' : '' ?>" href="/admin/articles">
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="3" width="12" height="10" rx="1.5"/>
        <line x1="5" y1="6.5" x2="11" y2="6.5"/>
        <line x1="5" y1="9.5" x2="9" y2="9.5"/>
      </svg>
      Liste d'articles
    </a>

    <a class="bo-link <?= $activePage === 'front' ? 'active' : '' ?>" href="/accueil" target="_blank">
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="8" cy="8" r="6"/>
        <path d="M2 8h12M8 2c-1.5 2-2 4-2 6s.5 4 2 6M8 2c1.5 2 2 4 2 6s-.5 4-2 6"/>
      </svg>
      Voir front office
    </a>
  </nav>

  <div class="bo-footer">
    <div class="bo-user">
      <div class="bo-avatar"><?= htmlspecialchars($userInitial, ENT_QUOTES, 'UTF-8') ?></div>
      <div style="flex:1;min-width:0;">
        <div class="bo-user-name"><?= htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="bo-user-role">Administrateur</div>
      </div>
    </div>
    <a class="bo-logout" href="/auth/logout.php">
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 8h7M11 6l2 2-2 2"/>
        <path d="M10 4V3a1 1 0 00-1-1H3a1 1 0 00-1 1v10a1 1 0 001 1h6a1 1 0 001-1v-1"/>
      </svg>
      Déconnexion
    </a>
  </div>
</aside>