import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';

const fetchJSON = async (endpoint, options = {}) => {
	const response = await fetch(`${KHImageSettings.restUrl}${endpoint}`, {
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': KHImageSettings.nonce,
		},
		credentials: 'same-origin',
		...options,
	});

	return response.json();
};

const Dashboard = () => {
	const [status, setStatus] = useState(null);
	const [queue, setQueue] = useState(null);
	const [loading, setLoading] = useState(true);
	const [directory, setDirectory] = useState(null);
	const [path, setPath] = useState('');
	const [selectedFiles, setSelectedFiles] = useState([]);
	const [notices, setNotices] = useState([]);
	const [queueFilter, setQueueFilter] = useState('all');
	const [quickSetup, setQuickSetup] = useState({
		mode: 'lossless',
		auto_resize: false,
		auto_convert_webp: true,
		enable_cdn: false,
	});

	const load = async () => {
		setLoading(true);
		const queueEndpoint = `/queue${queueFilter !== 'all' ? `?context=${queueFilter}` : ''}`;
		const directoryEndpoint = `/directory${path ? `?path=${encodeURIComponent(path)}` : ''}`;

		const [statusData, queueData, directoryData, noticesData] = await Promise.all([
			fetchJSON('/status'),
			fetchJSON(queueEndpoint),
			fetchJSON(directoryEndpoint),
			fetchJSON('/notices'),
		]);

		setStatus(statusData);
		setQueue(queueData);
		setDirectory(directoryData);
		setNotices(noticesData.notices || []);
		setQuickSetup({
			mode: statusData.settings.mode,
			auto_resize: statusData.settings.auto_resize,
			auto_convert_webp: statusData.settings.auto_convert_webp,
			enable_cdn: statusData.settings.enable_cdn,
		});
		setSelectedFiles([]);
		setLoading(false);
	};

	useEffect(() => {
		load();
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [queueFilter]);

	const loadDirectory = async newPath => {
		setPath(newPath);
		const directoryData = await fetchJSON(`/directory${newPath ? `?path=${encodeURIComponent(newPath)}` : ''}`);
		setDirectory(directoryData);
		setSelectedFiles([]);
	};

	const toggleFile = file => {
		setSelectedFiles(prev =>
			prev.includes(file) ? prev.filter(item => item !== file) : [...prev, file],
		);
	};

	const enqueueFiles = async () => {
		if (!selectedFiles.length) {
			return;
		}

		await fetchJSON('/directory/enqueue', {
			method: 'POST',
			body: JSON.stringify({ files: selectedFiles }),
		});

		setSelectedFiles([]);
		load();
	};

	const submitQuickSetup = async e => {
		e.preventDefault();
		await fetchJSON('/quick-setup', {
			method: 'POST',
			body: JSON.stringify(quickSetup),
		});
		load();
	};

	const dismissNotice = async notice => {
		try {
			await fetchJSON('/notices/dismiss', {
				method: 'POST',
				body: JSON.stringify({ id: notice.id, global: notice.global }),
			});
		} finally {
			setNotices(prev => prev.filter(item => item.id !== notice.id));
		}
	};

	if (loading || !status || !queue) {
		return <p>Loading KH Image status…</p>;
	}

	return (
		<div className="kh-image-admin-app">
			{notices.length > 0 && (
				<div className="kh-image-notice-area">
					{notices.map((notice, index) => (
						<div key={index} className={`notice notice-${notice.type} is-dismissible`}>
							<p>{notice.message}</p>
							<button
								type="button"
								className="notice-dismiss"
								aria-label="Dismiss this notice"
								onClick={() => dismissNotice(notice)}
							>
								<span className="screen-reader-text">Dismiss this notice.</span>
							</button>
						</div>
					))}
				</div>
			)}
			<h1>KH Image</h1>
			<section>
				<h2>Optimizer Status</h2>
				<p>Version: {status.version}</p>
				<p>Mode: {status.settings.mode}</p>
				<p>Auto WebP/AVIF: {status.settings.auto_convert_webp ? 'Enabled' : 'Disabled'}</p>
				<form onSubmit={submitQuickSetup} className="quick-setup-form">
					<h3>Quick Setup</h3>
					<label>
						Mode
						<select
							value={quickSetup.mode}
							onChange={e => setQuickSetup({ ...quickSetup, mode: e.target.value })}
						>
							<option value="lossless">Lossless</option>
							<option value="lossy">Lossy</option>
						</select>
					</label>
					<label>
						<input
							type="checkbox"
							checked={quickSetup.auto_resize}
							onChange={e => setQuickSetup({ ...quickSetup, auto_resize: e.target.checked })}
						/>
						Auto Resize Images
					</label>
					<label>
						<input
							type="checkbox"
							checked={quickSetup.auto_convert_webp}
							onChange={e => setQuickSetup({ ...quickSetup, auto_convert_webp: e.target.checked })}
						/>
						Generate WebP & AVIF
					</label>
					<label>
						<input
							type="checkbox"
							checked={quickSetup.enable_cdn}
							onChange={e => setQuickSetup({ ...quickSetup, enable_cdn: e.target.checked })}
						/>
						Enable CDN Delivery
					</label>
					<button className="button button-primary" type="submit">
						Save Quick Setup
					</button>
				</form>
			</section>
			<section>
				<h2>Queue</h2>
				<div className="queue-toolbar">
					<label>
						Filter:
						<select value={queueFilter} onChange={e => setQueueFilter(e.target.value)}>
							<option value="all">All</option>
							<option value="upload">Uploads</option>
							<option value="bulk">Bulk</option>
							<option value="directory">Directory</option>
						</select>
					</label>
					<button className="button" type="button" onClick={load}>
						Refresh
					</button>
				</div>
				<p>Jobs waiting: {queue.status.count}</p>
				{queue.jobs.length > 0 && (
					<table className="widefat">
						<thead>
							<tr>
								<th>Attachment</th>
								<th>Context</th>
								<th>Enqueued</th>
							</tr>
						</thead>
						<tbody>
							{queue.jobs.map((job, index) => (
								<tr key={index}>
									<td>{job.attachment_id || job.file_path}</td>
									<td>{job.context}</td>
									<td>{new Date(job.enqueued_at * 1000).toLocaleString()}</td>
								</tr>
							))}
						</tbody>
					</table>
				)}
			</section>
			<section>
				<h2>Directory Smush</h2>
				{directory && (
					<div className="kh-image-directory">
						<p>Path: /{directory.base}</p>
						<div className="kh-image-directory-grid">
							<div>
								<h3>Folders</h3>
								<ul>
									{directory.directories.map(dir => (
										<li key={dir.path}>
											<button
												type="button"
												className="button-link"
												onClick={() => loadDirectory(dir.path)}
											>
												{dir.name}
											</button>
										</li>
									))}
									{directory.base && (
										<li>
											<button
												type="button"
												className="button-link"
												onClick={() => loadDirectory(directory.base.split('/').slice(0, -1).join('/'))}
											>
												⬅ Up
											</button>
										</li>
									)}
								</ul>
							</div>
							<div>
								<h3>Files</h3>
								<ul className="kh-image-file-list">
									{directory.files.map(file => (
										<li key={file.path}>
											<label>
												<input
													type="checkbox"
													checked={selectedFiles.includes(file.path)}
													onChange={() => toggleFile(file.path)}
												/>
												{file.name} ({(file.size / 1024).toFixed(1)} KB)
											</label>
										</li>
									))}
								</ul>
								<button className="button button-primary" onClick={enqueueFiles} disabled={!selectedFiles.length}>
									Enqueue Selected ({selectedFiles.length})
								</button>
							</div>
						</div>
					</div>
				)}
			</section>
		</div>
	);
};

const mount = document.getElementById('kh-image-admin-root');

if (mount) {
	const root = createRoot(mount);
	root.render(<Dashboard />);
}
