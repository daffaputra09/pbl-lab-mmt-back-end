CREATE DATABASE db_lab_mmt;

-- Table: User
CREATE TABLE "user" (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password TEXT NOT NULL
);

-- Table: Kategori
CREATE TABLE kategori (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL
);

-- Table: Project
CREATE TABLE project (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    id_kategori INTEGER NOT NULL,
    video_url TEXT,
    image_url TEXT[], 
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id) ON DELETE RESTRICT
);

-- Table: Rating
CREATE TABLE rating (
    id SERIAL PRIMARY KEY,
    id_project INTEGER NOT NULL,
    name VARCHAR(255) NOT NULL, 
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP, 
    FOREIGN KEY (id_project) REFERENCES project(id) ON DELETE CASCADE
);

-- Table: Tag
CREATE TABLE tag (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL
);

-- Table: Project_Tag (Junction table)
CREATE TABLE project_tag (
    id SERIAL PRIMARY KEY,
    id_project INTEGER NOT NULL,
    id_tag INTEGER NOT NULL,
    UNIQUE(id_project, id_tag), 
    FOREIGN KEY (id_project) REFERENCES project(id) ON DELETE CASCADE,
    FOREIGN KEY (id_tag) REFERENCES tag(id) ON DELETE CASCADE
);

-- Table: Galeri
CREATE TABLE galeri (
    id SERIAL PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    file_url TEXT NOT NULL,
    tanggal_kegiatan DATE,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- Table: Berita
CREATE TABLE berita (
    id SERIAL PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    description TEXT,
    image_url TEXT,
    id_user INTEGER, 
    status VARCHAR(50) DEFAULT 'published',
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES "user"(id) ON DELETE SET NULL
);

-- Table: Event
CREATE TABLE event (
    id SERIAL PRIMARY KEY,
    image_url TEXT,
    judul VARCHAR(255) NOT NULL,
    description TEXT,
    tanggal_event DATE
);

-- Table: Anggota
CREATE TABLE anggota (
    id SERIAL PRIMARY KEY,
    nama TEXT NOT NULL,
    role TEXT,
    image_url TEXT,
    skills TEXT[],
    media_social TEXT[]
);

-- Table: Project_Anggota (Junction table)
CREATE TABLE project_anggota (
    id SERIAL PRIMARY KEY,
    id_project INTEGER NOT NULL,
    id_anggota INTEGER NOT NULL,
    UNIQUE(id_project, id_anggota),
    FOREIGN KEY (id_project) REFERENCES project(id) ON DELETE CASCADE,
    FOREIGN KEY (id_anggota) REFERENCES anggota(id) ON DELETE CASCADE
);

-- Create indexes 
CREATE INDEX idx_project_kategori ON project(id_kategori);
CREATE INDEX idx_rating_project ON rating(id_project);
CREATE INDEX idx_project_tag_project ON project_tag(id_project);
CREATE INDEX idx_project_tag_tag ON project_tag(id_tag);
CREATE INDEX idx_berita_user ON berita(id_user);
CREATE INDEX idx_project_anggota_project ON project_anggota(id_project);
CREATE INDEX idx_project_anggota_anggota ON project_anggota(id_anggota);
