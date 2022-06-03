@foreach ($inventory_orders as $io)
    <tr>
        <td>{{ $io->io_id }}</td>
        <td>{{ $io->vendor }}</td>
        <td>{{ $io->company_name }}</td>
        <td>{{ $io->contact_detail }}</td>
        <td>{{ $io->address }}</td>
        <td class="text-end">{{ sprintf('%.2f', $io->tax) }}</td>
        <td class="text-end fit">{{ sprintf('%.2f', $io->shipping_fee) }}</td>
        {{-- total --}}
        {{-- <td class="text-end">
            {{ sprintf('%.2f', $io->io2p_total_price + $io->shipping_fee + $io->tax) }}</td> --}}
        <td class="fit">{{ $io->eta }}</td>
        <td class="text-nowrap">
            <div class="d-flex justify-content-around">

                <button class="view-details btn btn-success px-3 py-1" data-io-id="{{ $io->io_id }}"
                    data-supplier-id="{{ $io->supplier_id }}"
                    data-bs-toggle="modal" data-bs-target="#detailsModalContent">View Details</button>
                <form action="{{ route('purchase_order_cancel') }}" method="POST" class="px-1">
                    @csrf
                    <input type="hidden" value="{{ $io->io_id }}" name="io_id">
                    <input type="hidden" value="{{ $io->supplier_id }}" name="supplier_id">
                    {{-- Need to pass url_params to set GET params after redirection from an action like delete --}}

                    <button type="submit" class="btn btn-danger text-primary">
                        Cancel
                    </button>
                </form>
            </div>

        </td>
    </tr>
@endforeach
