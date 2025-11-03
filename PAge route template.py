@app.route('/<current_page>', methods=['POST', 'GET'])
def <current_page>():
    # ─────────────────────────────────────────────
    # ① Navigation Tracking
    # Track previous page for back button or flow control
    # ─────────────────────────────────────────────
    previous_page = session.get('last_visited', url_for('<previous_page>'))
    session['last_visited'] = '<current_page>'

    # ─────────────────────────────────────────────
    # ② POST: Form Submission Handling
    # Capture checkboxes and associated dynamic input fields
    # ─────────────────────────────────────────────
    if request.method == 'POST':
        checkbox_data = session.setdefault('checkbox_data', {})

        # Store checkbox selection
        selected_xy = request.form.getlist('selected_xy')
        checkbox_data['selected_xy'] = {'preselected': selected_xy}

        # Initialize input containers
        xy_input_type_values = {}
        xy_value_a = {}
        xy_value_b = {}

        # Parse dynamic inputs tied to checkbox selection
        for key in selected_xy:
            input_type = request.form.get(f'xyType_{key}', '').strip()
            if input_type:
                xy_input_type_values[key] = input_type

            value_a = request.form.get(f'xyValueA_{key}', '').strip()
            value_b = request.form.get(f'xyValueB_{key}', '').strip()

            if value_a:
                xy_value_a[key] = value_a
            if value_b:
                xy_value_b[key] = value_b

        # Store input values in session
        session_data = session.setdefault('data', {})
        session_data['xy_input_type_values'] = xy_input_type_values
        session_data['xy_value_a'] = xy_value_a
        session_data['xy_value_b'] = xy_value_b

        # Commit session and redirect to next page
        session.modified = True  
        return redirect(url_for('additional_building_work_page'))

    # ─────────────────────────────────────────────
    # ③ GET: Prepopulate Form State from Session
    # Pull checkbox state and inputs to repopulate UI
    # ─────────────────────────────────────────────
    preselected_xy = session.get('checkbox_data', {}).get('selected_xy', {}).get('preselected', [])

    data = {
        "selected_xy": {"data": {}, "preselected": preselected_xy.copy()},
        "xy_input_type_values": session.get('data', {}).get('xy_input_type_values', {}),
        "xy_value_a": session.get('data', {}).get('xy_value_a', {}),
        "xy_value_b": session.get('data', {}).get('xy_value_b', {})
    }

    # ─────────────────────────────────────────────
    # ④ Data Fetching
    # Pull live line data from Google Sheet or external source
    # ─────────────────────────────────────────────
    sheet_data = fetch_data()

    # ─────────────────────────────────────────────
    # ⑤ Data Filtering and Mapping
    # Extract only rows relevant to the current prefix group
    # ─────────────────────────────────────────────
    for row in sheet_data:
        line_code = row.get('Line Code', '')
        alphanumeric_code = to_alphanumeric_code(line_code)
        internal_description = row.get('Internal Description', '')
        include = row.get('Include', '')

        if alphanumeric_code.startswith('xy') and alphanumeric_code[-1].isdigit():
            data["selected_xy"]["data"][line_code] = {
                "description": internal_description,
                "is_included": include == 'Y'
            }
            if line_code in data["selected_xy"]["preselected"] and line_code not in data["selected_xy"]["preselected"]:
                data["selected_xy"]["preselected"].append(line_code)

    # ─────────────────────────────────────────────
    # ⑥ Render Template
    # Return form with fully preprocessed context
    # ─────────────────────────────────────────────
    return render_template(
        'form.html',
        <current_page_flag>=True,
        previous_page=previous_page,
        next_page='<next_page>',
        title="<Page Title>",
        data=data,
        xy_value_a=data.get('xy_value_a', {}),
        xy_value_b=data.get('xy_value_b', {})
    )
