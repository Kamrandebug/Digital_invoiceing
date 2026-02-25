<div class="modal effect-fall fade" id="invoice_modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h6 class="modal-title" id="invoice_modal_title">Invoice</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            {{-- AJAX content loads here --}}
            <div class="modal-body">
				<form action="" method="POST" id="" novalidate>
					<div class="row g-2">
						<div class="col-md-4">
							<div class="row g-2">
								<div class="col-md-12">
									<label>Customer</label>
									<div class="clearfix form-select-sm2">
										<select class="js-example-basic-single" name="state">
											<option value="s-1">Selection-1</option>
											<option value="s-2">Selection-2</option>
											<option value="s-3">Selection-3</option>
											<option value="s-4">Selection-4</option>
											<option value="s-5">Selection-5</option>
										</select>
									</div>
								</div>
								<div class="col-md-12">
									<label>Address</label>
									<div class="clearfix">
										<textarea class="form-control form-control-sm address-h" rows="3"></textarea>
									</div>
								</div>
							</div>
						</div>
						<div class="col-md-2">
							<div class="row g-2">
								<div class="col-md-12">
									<label>Date</label>
									<input type="date" class="form-control form-control-sm" name="start_date" id="startDate" value="{{ request('start_date') }}"
onchange="debounceSearch()">
								</div>
								<div class="col-md-12">
									<label>Term Days</label>
									<input type="date" class="form-control form-control-sm" name="start_date" id="startDate" value="{{ request('start_date') }}"
onchange="debounceSearch()">
								</div>
								<div class="col-md-12">
									<label>Due Date</label>
									<input type="date" class="form-control form-control-sm" name="start_date" id="startDate" value="{{ request('start_date') }}"
onchange="debounceSearch()">
								</div>
							</div>
						</div>
						<div class="col-md-2">
							<div class="row g-2">
								<div class="col-md-12">
									<label>Doc No.</label>
									<input type="text" class="form-control form-control-sm" name="doc-no" id="doc-no" value="" >
								</div>
							</div>
						</div>

						<div class="col-xl-12">
							<div class=""> 
								<table class="table nowrap text-nowrap table-borderless border mt-3 table-inv"> 
									<thead>
										<tr>
											<th width="15%">Product / Services</th>
											<th width="15%">Description</th>
											<th width="5%">Qty</th>
											<th width="8%">Rate</th>
											<th width="8%">Amount</th>
											<th width="8%">Disc %</th>
											<th width="8%">Disc Amount</th>
											<th width="8%">GST Rate</th>
											<th width="8%">GST Amount</th>
											<th width="8%">Net</th>
											<th></th>
										</tr> 
									</thead> 
									<tbody>
										<tr> 
											<td class="form-select-sm">
												<select class="js-example-basic-single" name="state">
													<option value="s-1">Selection-1</option>
													<option value="s-2">Selection-2</option>
													<option value="s-3">Selection-3</option>
													<option value="s-4">Selection-4</option>
													<option value="s-5">Selection-5</option>
												</select>
											</td>
											<td>
												<textarea rows="1" class="form-control form-control-sm" placeholder="Enter Description"></textarea>
											</td>
											<td class="invoice-quantity-container">
												<div class="input-group border rounded flex-nowrap">
													<button class="btn btn-sm btn-icon btn-primary input-group-text flex-fill product-quantity-minus"><i class="ri-subtract-line"></i></button>
													<input type="text" class="form-control form-control-sm border-0 text-center w-100" aria-label="quantity" id="product-quantity4" value="1">
													<button class="btn btn-sm btn-icon btn-primary input-group-text flex-fill product-quantity-plus"><i class="ri-add-line"></i></button>
												</div>
											</td>
											<td><input class="form-control form-control-sm" placeholder="" type="text" value="$60.00"></td>
											<td><input class="form-control form-control-sm" placeholder="" type="text" value="$120.00"></td>
											<td><input class="form-control form-control-sm" placeholder="" type="text" value="$120.00"></td>
											<td><input class="form-control form-control-sm" placeholder="" type="text" value="$120.00"></td>
											<td><input class="form-control form-control-sm" placeholder="" type="text" value="$120.00"></td>
											<td><input class="form-control form-control-sm" placeholder="" type="text" value="$120.00"></td>
											<td><input class="form-control form-control-sm" placeholder="" type="text" value="$120.00"></td>
											<td>
												<button class="btn btn-sm btn-icon btn-danger-light"><i class="ri-delete-bin-5-line"></i></button>
											</td>
										</tr> 
									</tbody> 
								</table> 
								<div class="my-3">
									<a class="btn btn-primary-light" href="javascript:void(0);"><i class="bi bi-plus-lg"></i> Add Product</a>
								</div>
								<div class="row mt-4">
									<div class="col-md-9">
										<div class="clearfix w-50">
											<label>Notes:</label>
											<textarea class="form-control" rows="4"></textarea>
										</div>
									</div>
									<div class="col text-end">
										<table class="table table-sm text-nowrap mb-0 table-borderless">
											<tbody>
												<tr>
													<th scope="row">
														<div class="fw-medium">Sub Total :</div>
													</th>
													<td>
														<input type="text" class="form-control invoice-amount-input w-100" readonly placeholder="Enter Amount" value="$1209.89">
													</td>
												</tr>
												<tr>
													<th scope="row">
														<div class="fw-medium">Avail Discount :</div>
													</th>
													<td>
														<input type="text" class="form-control invoice-amount-input w-100" readonly placeholder="Enter Amount" value="$29.98">
													</td>
												</tr>
												<tr>
													<th scope="row">
														<div class="fw-medium">GST :</div>
													</th>
													<td>
														<input type="text" class="form-control invoice-amount-input w-100" readonly placeholder="Enter Amount" value="$129.00">
													</td>
												</tr>														
												<tr>
													<th scope="row">
														<div class="fs-14 fw-medium">Total :</div>
													</th>
													<td>
														<input type="text" class="form-control invoice-amount-input w-100" readonly placeholder="Enter Amount" value="$1,071.89">
													</td>
												</tr>
											</tbody>
										</table>
								</div>
								</div>

							</div>
						</div>
						
					</div>
				</form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
                <button type="button" class="btn btn-sm btn-primary" id="invoice_modal_save_btn">
                    <i class="fas fa-save"></i>
                    <span id="invoice_modal_save_label">Save Invoice</span>
                </button>
            </div>

        </div>
    </div>
</div>
