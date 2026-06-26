import { useRef } from "react";
import { Button } from "antd";
import { useReactToPrint } from "react-to-print";

export default function Sep() {
    const printRef = useRef(null);

    const handlePrint = useReactToPrint({
        contentRef: printRef,
        documentTitle: "SEP",
    });

    return (
        <>
            <div style={{ marginBottom: 16 }}>
                <Button type="primary" onClick={handlePrint}>
                    Download PDF
                </Button>
            </div>

            <div
                ref={printRef}
                style={{
                    background: "#fff",
                    padding: 24,
                    width: "210mm",
                    minHeight: "297mm",
                    margin: "0 auto",
                }}
            >
                <h2 style={{ textAlign: "center" }}>
                    SURAT ELEGIBILITAS PESERTA
                </h2>

                <table style={{ width: "100%" }}>
                    <tbody>
                        <tr>
                            <td width="150">No SEP</td>
                            <td>1301R0010626V000001</td>
                        </tr>

                        <tr>
                            <td>Nama Pasien</td>
                            <td>BUDI SANTOSO</td>
                        </tr>

                        <tr>
                            <td>Diagnosa</td>
                            <td>Diabetes Mellitus</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </>
    );
}